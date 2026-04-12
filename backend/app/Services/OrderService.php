<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Admin audit report: all staff order status activity (paginated).
     *
     * @return LengthAwarePaginator<int, OrderStatusHistory>
     */
    public function listStatusHistory(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = OrderStatusHistory::query()
            ->with(['order', 'actor'])
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->whereHas('order', function ($oq) use ($term) {
                    $oq->where('order_number', 'like', "%{$term}%");
                })->orWhereHas('actor', function ($aq) use ($term) {
                    $aq->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            });
        }

        if (! empty($filters['actor_user_id'])) {
            $query->where('actor_user_id', (int) $filters['actor_user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Status history rows for a single order (staff/admin audit).
     *
     * @return LengthAwarePaginator<int, OrderStatusHistory>
     */
    public function listStatusHistoryForOrder(int $orderId, int $perPage = 20): LengthAwarePaginator
    {
        return OrderStatusHistory::query()
            ->where('order_id', $orderId)
            ->with(['actor'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * List orders with filters and pagination.
     * Staff/admin see all orders; customers see only their own.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()->with($this->orderRelations());

        if (! empty($filters['status'])) {
            $query->byStatus(OrderStatus::from($filters['status']));
        }

        if (! empty($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Find an order by ID with items and user loaded.
     */
    public function find(int $orderId): Order
    {
        return Order::with($this->orderRelations(includeProductImages: true))->findOrFail($orderId);
    }

    /**
     * Create a new order with items.
     * Validates stock availability against the inventory table.
     * Calculates totals and generates a unique order number.
     */
    public function create(array $data, ?User $actor = null): Order
    {
        $this->validateStock($data['items']);
        $this->validateAppointmentLink($data, $actor);

        return DB::transaction(function () use ($data, $actor) {
            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 2);

            $order = Order::create([
                'user_id' => $data['user_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'walk_in_name' => $data['walk_in_name'] ?? null,
                'walk_in_phone' => $data['walk_in_phone'] ?? null,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::Pending,
                'total_amount' => 0,
                'discount_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $index => $itemData) {
                $variant = ProductVariant::with('product')->findOrFail($itemData['product_variant_id']);
                if (! $variant->is_active) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_variant_id" => __('This product option is no longer available.'),
                    ]);
                }
                $unitPrice = (float) $variant->unitPrice();
                $subtotal = $unitPrice * $itemData['quantity'];
                $totalAmount += $subtotal;

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $this->inventoryService->deduct($variant, $itemData['quantity']);
            }

            if ($discountAmount > $totalAmount) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Discount amount cannot be greater than order subtotal.',
                ]);
            }

            $finalAmount = round($totalAmount - $discountAmount, 2);
            $order->update([
                'discount_amount' => $discountAmount,
                'total_amount' => $finalAmount,
            ]);

            // Billing is intentionally deferred until staff confirmation / in-person payment stage.
            $order->load(array_merge($this->orderRelations(), ['bill']));

            $this->recordStaffOrderActivity(
                $order,
                $actor,
                OrderStatusHistory::ACTION_CREATED,
                null,
                OrderStatus::Pending,
            );

            return $order;
        });
    }

    /**
     * Update order status with lifecycle transition validation.
     */
    public function updateStatus(Order $order, OrderStatus $newStatus, ?User $actor = null): Order
    {
        if (! $order->status->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from '{$order->status->label()}' to '{$newStatus->label()}'.",
            ]);
        }

        $fromStatus = $order->status;

        $order->update(['status' => $newStatus]);

        $order = $order->fresh($this->orderRelations());

        $this->recordStaffOrderActivity(
            $order,
            $actor,
            OrderStatusHistory::ACTION_STATUS_UPDATED,
            $fromStatus,
            $newStatus,
        );

        return $order;
    }

    /**
     * Cancel an order with role-based permission check.
     */
    public function cancel(Order $order, User $user): Order
    {
        if (! $order->canBeCancelledBy($user)) {
            throw ValidationException::withMessages([
                'status' => 'This order cannot be cancelled.',
            ]);
        }

        $fromStatus = $order->status;

        $order->update(['status' => OrderStatus::Cancelled]);

        $order->load('items.productVariant');
        foreach ($order->items as $item) {
            $this->inventoryService->restore($item->productVariant, $item->quantity);
        }

        $this->billingService->handleOrderCancellation($order, $user);

        $order = $order->fresh(array_merge($this->orderRelations(), ['bill']));

        $this->recordStaffOrderActivity(
            $order,
            $user,
            OrderStatusHistory::ACTION_STATUS_UPDATED,
            $fromStatus,
            OrderStatus::Cancelled,
        );

        return $order;
    }

    /**
     * Validate that all items have sufficient stock in the inventory.
     *
     * @throws ValidationException
     */
    private function validateStock(array $items): void
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $inventory = Inventory::where('product_variant_id', $item['product_variant_id'])->first();

            if (! $inventory || ! $inventory->isInStock($item['quantity'])) {
                $available = $inventory?->quantity ?? 0;
                $errors["items.{$index}.product_variant_id"] = "Insufficient stock. Requested: {$item['quantity']}, available: {$available}.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate appointment linkage for order creation.
     *
     * Rules:
     * - Skip when appointment_id is missing.
     * - Appointment must exist.
     * - If appointment status column exists, only active/booked statuses can be linked.
     * - If appointment has user_id, it must match order user_id.
     */
    private function validateAppointmentLink(array $data, ?User $actor = null): void
    {
        $appointmentId = $data['appointment_id'] ?? null;
        if ($appointmentId === null) {
            return;
        }

        if (! Schema::hasTable('appointments')) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Appointments are not available yet.',
            ]);
        }

        $appointment = DB::table('appointments')->where('id', $appointmentId)->first();
        if (! $appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Selected appointment does not exist.',
            ]);
        }

        if (Schema::hasColumn('appointments', 'status') && isset($appointment->status)) {
            $status = strtolower((string) $appointment->status);
            if (in_array($status, ['cancelled', 'completed', 'no_show'], true)) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'Only active appointments can be linked to an order.',
                ]);
            }
        }

        $orderUserId = $data['user_id'] ?? null;
        $appointmentUserId = $appointment->user_id ?? null;

        if ($orderUserId !== null && $appointmentUserId !== null && (int) $orderUserId !== (int) $appointmentUserId) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Appointment does not belong to the selected customer.',
            ]);
        }

        if ($actor?->isCustomer() && $appointmentUserId !== null && (int) $appointmentUserId !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'appointment_id' => 'You can only link your own appointment.',
            ]);
        }
    }

    /**
     * Generate a unique order number in the format ORD-YYYYMMDD-XXXXX.
     */
    private function generateOrderNumber(): string
    {
        $datePrefix = 'ORD-'.now()->format('Ymd').'-';

        $lastOrder = Order::withTrashed()
            ->where('order_number', 'like', $datePrefix.'%')
            ->orderByDesc('order_number')
            ->first();

        if ($lastOrder) {
            $lastSequence = (int) substr($lastOrder->order_number, -5);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $datePrefix.str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Records staff/admin activity only (skipped for customers and when actor is missing).
     */
    private function recordStaffOrderActivity(
        Order $order,
        ?User $actor,
        string $action,
        ?OrderStatus $fromStatus,
        OrderStatus $toStatus,
    ): void {
        if ($actor === null || ! $actor->isAdminOrStaff()) {
            return;
        }

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'actor_user_id' => $actor->id,
            'action' => $action,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
        ]);
    }

    /**
     * Base eager-load map for order payloads.
     * Guard appointment relation behind a table check to avoid SQL failures
     * in environments where the appointments module/migrations are not present.
     *
     * @return array<int, string>
     */
    private function orderRelations(bool $includeProductImages = false): array
    {
        $productRelation = $includeProductImages
            ? 'items.productVariant.product.images'
            : 'items.productVariant.product';

        $relations = [$productRelation, 'user'];

        if (Schema::hasTable('appointments')) {
            $relations[] = 'appointment';
        }

        return $relations;
    }
}
