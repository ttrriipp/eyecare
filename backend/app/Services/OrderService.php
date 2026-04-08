<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * List orders with filters and pagination.
     * Staff/admin see all orders; customers see only their own.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['items.productVariant.product', 'user']);

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
        return Order::with(['items.productVariant.product.images', 'user'])->findOrFail($orderId);
    }

    /**
     * Create a new order with items.
     * Validates stock availability against the inventory table.
     * Calculates totals and generates a unique order number.
     */
    public function create(array $data): Order
    {
        $this->validateStock($data['items']);

        return DB::transaction(function () use ($data) {
            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 2);

            $order = Order::create([
                'user_id' => $data['user_id'] ?? null,
                'walk_in_name' => $data['walk_in_name'] ?? null,
                'walk_in_phone' => $data['walk_in_phone'] ?? null,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::Pending,
                'total_amount' => 0,
                'discount_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $itemData) {
                $variant = ProductVariant::with('product')->findOrFail($itemData['product_variant_id']);
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

            $this->billingService->createForOrder($order);

            return $order->load(['items.productVariant.product', 'user', 'bill']);
        });
    }

    /**
     * Update order status with lifecycle transition validation.
     */
    public function updateStatus(Order $order, OrderStatus $newStatus): Order
    {
        if (! $order->status->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from '{$order->status->label()}' to '{$newStatus->label()}'.",
            ]);
        }

        $order->update(['status' => $newStatus]);

        return $order->fresh(['items.productVariant.product', 'user']);
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

        $order->update(['status' => OrderStatus::Cancelled]);

        $order->load('items.productVariant');
        foreach ($order->items as $item) {
            $this->inventoryService->restore($item->productVariant, $item->quantity);
        }

        $this->billingService->handleOrderCancellation($order);

        return $order->fresh(['items.productVariant.product', 'user', 'bill']);
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
}
