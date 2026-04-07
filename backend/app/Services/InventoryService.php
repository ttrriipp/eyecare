<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryService
{
    /**
     * Paginate products with optional inventory row (for web stock overview).
     */
    public function paginateProductsForInventory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()
            ->leftJoin('inventory as inventory_sort', 'inventory_sort.product_id', '=', 'products.id')
            ->select('products.*')
            ->with(['category', 'images', 'inventory']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (($filters['low_stock'] ?? false) === true) {
            $query->whereHas('inventory', function ($q) {
                $q->whereColumn('inventory.quantity', '<=', 'inventory.reorder_level');
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('inventory_sort.updated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('inventory_sort.updated_at', '<=', $filters['date_to']);
        }

        $sortBy = $filters['sort_by'] ?? null;
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if ($sortBy === 'name') {
            $query->orderBy('products.name', $sortDir === 'asc' ? 'asc' : 'desc');
        } elseif ($sortBy === 'quantity') {
            $query->orderBy('inventory_sort.quantity', $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query
                ->orderByRaw('CASE WHEN inventory_sort.updated_at IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('inventory_sort.updated_at', 'desc')
                ->orderBy('products.name', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Inventory::query()
            ->with([
                'product.category',
                'product.images',
            ]);

        if (($filters['low_stock'] ?? false) === true) {
            $query->lowStock();
        }

        $sortBy = $filters['sort_by'] ?? 'updated_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function findByProduct(Product $product): Inventory
    {
        return Inventory::query()
            ->with([
                'product.category',
                'product.images',
            ])
            ->firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 0, 'reorder_level' => 0, 'notes' => null],
            );
    }

    public function update(Inventory $inventory, array $data): Inventory
    {
        $inventory->update($data);

        return $inventory->fresh([
            'product.category',
            'product.images',
        ]);
    }

    public function lowStock(int $perPage = 15): LengthAwarePaginator
    {
        return $this->list(filters: ['low_stock' => true], perPage: $perPage);
    }

    /**
     * Deduct stock for a product. Used when an order is placed.
     *
     * @throws \RuntimeException if insufficient stock
     */
    public function deduct(Product $product, int $quantity): void
    {
        $inventory = Inventory::where('product_id', $product->id)->first();

        if (! $inventory || ! $inventory->isInStock($quantity)) {
            $available = $inventory?->quantity ?? 0;
            throw new \RuntimeException(
                "Insufficient stock for product [{$product->name}]. Requested: {$quantity}, available: {$available}."
            );
        }

        $inventory->decrement('quantity', $quantity);
    }

    /**
     * Restore stock for a product. Used when an order is cancelled.
     */
    public function restore(Product $product, int $quantity): void
    {
        $inventory = Inventory::where('product_id', $product->id)->first();

        if ($inventory) {
            $inventory->increment('quantity', $quantity);
        }
    }
}
