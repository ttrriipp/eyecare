<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * List orders with filters and pagination.
     * Staff/admin see all orders; customers see only their own.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['items.product', 'user']);

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
        return Order::with(['items.product.images', 'user'])->findOrFail($orderId);
    }

    /**
     * Create a new order with items.
     * Validates stock availability against the inventory table.
     * Calculates totals and generates a unique order number.
     */
    public function create(array $data): Order
    {
        // Validate stock availability before creating the order
        $this->validateStock($data['items']);

        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'user_id' => $data['user_id'] ?? null,
                'walk_in_name' => $data['walk_in_name'] ?? null,
                'walk_in_phone' => $data['walk_in_phone'] ?? null,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::Pending,
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $itemData) {
                $product = \App\Models\Product::findOrFail($itemData['product_id']);
                $unitPrice = $product->price;
                $subtotal = $unitPrice * $itemData['quantity'];
                $totalAmount += $subtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            return $order->load(['items.product', 'user']);
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

        return $order->fresh(['items.product', 'user']);
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

        return $order->fresh(['items.product', 'user']);
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
            $inventory = Inventory::where('product_id', $item['product_id'])->first();

            if (! $inventory || ! $inventory->isInStock($item['quantity'])) {
                $available = $inventory?->quantity ?? 0;
                $errors["items.{$index}.product_id"] = "Insufficient stock. Requested: {$item['quantity']}, available: {$available}.";
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
        $datePrefix = 'ORD-' . now()->format('Ymd') . '-';

        $lastOrder = Order::withTrashed()
            ->where('order_number', 'like', $datePrefix . '%')
            ->orderByDesc('order_number')
            ->first();

        if ($lastOrder) {
            $lastSequence = (int) substr($lastOrder->order_number, -5);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $datePrefix . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
    }
}
