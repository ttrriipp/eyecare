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
            ->with(['category', 'images', 'inventory']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (($filters['low_stock'] ?? false) === true) {
            $query->whereHas('inventory', function ($q) {
                $q->whereColumn('inventory.quantity', '<=', 'inventory.reorder_level');
            });
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

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
}
