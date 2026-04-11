<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventoryService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Inventory::query()
            ->with([
                'productVariant.product.category',
                'productVariant.product.images',
                'adjustments.adjustedBy',
            ]);

        if (($filters['low_stock'] ?? false) === true) {
            $query->lowStock();
        }

        $sortBy = $filters['sort_by'] ?? 'updated_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Default variant inventory for admin stock edit (legacy entry without ?variant=).
     */
    public function findByProduct(Product $product): Inventory
    {
        $product->loadMissing('defaultVariant');

        $variant = $product->defaultVariant;
        if (! $variant) {
            throw new \RuntimeException('Product is missing a default variant.');
        }

        return $this->findForVariant($variant);
    }

    /**
     * Inventory row for a variant (creates row if missing).
     */
    public function findForVariant(ProductVariant $variant): Inventory
    {
        return Inventory::query()
            ->with([
                'productVariant.product.category',
                'productVariant.product.images',
                'adjustments.adjustedBy',
            ])
            ->firstOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'quantity' => 0,
                    'reorder_level' => 0,
                    'reorder_quantity' => 0,
                    'batch_number' => null,
                    'expires_at' => null,
                    'notes' => null,
                ],
            );
    }

    public function update(Inventory $inventory, array $data, ?int $adjustedBy = null): Inventory
    {
        $quantityBefore = $inventory->quantity;
        $quantityAfter = array_key_exists('quantity', $data)
            ? (int) $data['quantity']
            : $quantityBefore;

        $reason = $data['adjustment_reason'] ?? null;
        unset($data['adjustment_reason']);

        $inventory->update($data);

        if ($quantityAfter !== $quantityBefore) {
            $delta = $quantityAfter - $quantityBefore;
            $type = $delta > 0 ? 'add' : 'subtract';

            $inventory->adjustments()->create([
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'delta' => $delta,
                'adjustment_type' => $type,
                'reason' => $reason,
                'adjusted_by' => $adjustedBy,
            ]);
        }

        return $inventory->fresh([
            'productVariant.product.category',
            'productVariant.product.images',
            'adjustments.adjustedBy',
        ]);
    }

    public function lowStock(int $perPage = 15): LengthAwarePaginator
    {
        return $this->list(filters: ['low_stock' => true], perPage: $perPage);
    }

    /**
     * Deduct stock for a variant line. Used when an order is placed.
     *
     * @throws \RuntimeException if insufficient stock
     */
    public function deduct(ProductVariant $variant, int $quantity): void
    {
        $inventory = Inventory::where('product_variant_id', $variant->id)->first();

        if (! $inventory || ! $inventory->isInStock($quantity)) {
            $available = $inventory?->quantity ?? 0;
            $label = $variant->product->name ?? 'product';
            throw new \RuntimeException(
                "Insufficient stock for [{$label}]. Requested: {$quantity}, available: {$available}."
            );
        }

        $inventory->decrement('quantity', $quantity);
    }

    /**
     * Restore stock for a variant line. Used when an order is cancelled.
     */
    public function restore(ProductVariant $variant, int $quantity): void
    {
        $inventory = Inventory::where('product_variant_id', $variant->id)->first();

        if ($inventory) {
            $inventory->increment('quantity', $quantity);
        }
    }

    /**
     * Make a stock adjustment for a specific variant.
     *
     * @param  'add'|'remove'|'set'  $type
     *
     * @throws \InvalidArgumentException if resulting quantity would be negative
     */
    public function adjust(
        ProductVariant $variant,
        string $type,
        int $quantity,
        string $reason,
        ?string $notes = null,
        ?int $adjustedBy = null,
    ): Inventory {
        $inventory = Inventory::firstOrCreate(
            ['product_variant_id' => $variant->id],
            ['quantity' => 0, 'reorder_level' => 0, 'reorder_quantity' => 0],
        );

        $newQuantity = match ($type) {
            'add' => $inventory->quantity + $quantity,
            'remove' => max(0, $inventory->quantity - $quantity),
            'set' => $quantity,
            default => throw new \InvalidArgumentException("Unknown adjustment type: {$type}"),
        };

        $reasonValue = filled($notes) ? "{$reason}: {$notes}" : $reason;

        return $this->update(
            $inventory,
            ['quantity' => $newQuantity, 'adjustment_reason' => $reasonValue],
            $adjustedBy,
        );
    }

    /**
     * Eagerly load all variants + their inventory rows for a product.
     */
    public function variantsWithInventory(Product $product): Collection
    {
        return $product->variants()
            ->with('inventory')
            ->get();
    }

    /**
     * Paginate inventory_adjustments with optional filters.
     * Used by the Adjustment History admin page.
     */
    public function paginateAdjustments(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = InventoryAdjustment::query()
            ->with(['inventory.productVariant.product', 'adjustedBy'])
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->whereHas('inventory.productVariant.product', fn ($q) => $q->where('name', 'like', "%{$term}%")
            );
        }

        if (! empty($filters['product_id'])) {
            $query->whereHas('inventory.productVariant', fn ($q) => $q->where('product_id', $filters['product_id'])
            );
        }

        if (! empty($filters['reason'])) {
            $query->where('reason', 'like', $filters['reason'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['adjusted_by'])) {
            $query->where('adjusted_by', $filters['adjusted_by']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Return the last N adjustments for a specific product (across all its variants).
     */
    public function recentAdjustmentsForProduct(int $productId, int $limit = 5): Collection
    {
        return InventoryAdjustment::query()
            ->whereHas('inventory.productVariant', fn ($q) => $q->where('product_id', $productId)
            )
            ->with(['inventory.productVariant', 'adjustedBy'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
