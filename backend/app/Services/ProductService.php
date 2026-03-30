<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['category', 'images'])
            ->withAvg('feedbacks as average_rating', 'rating')
            ->withCount(['feedbacks as reviews_count']);

        if (! ($filters['include_inactive'] ?? false)) {
            $query->active();
        }

        if (! empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (! empty($filters['brand'])) {
            $query->byBrand($filters['brand']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->priceRange(
                $filters['min_price'] ?? null,
                $filters['max_price'] ?? null,
            );
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::with(['category', 'images'])
            ->withAvg('feedbacks as average_rating', 'rating')
            ->withCount(['feedbacks as reviews_count'])
            ->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $product = Product::create($data);

        return $product->load(['category', 'images']);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh(['category', 'images']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function addImage(Product $product, string $imageUrl, int $sortOrder = 0): ProductImage
    {
        return $product->images()->create([
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
        ]);
    }

    public function deleteImage(ProductImage $image): void
    {
        $image->delete();
    }

    public function listCategories(): Collection
    {
        return \App\Models\ProductCategory::orderBy('name')->get();
    }

    public function createCategory(array $data): \App\Models\ProductCategory
    {
        return \App\Models\ProductCategory::create($data);
    }

    public function updateCategory(\App\Models\ProductCategory $category, array $data): \App\Models\ProductCategory
    {
        $category->update($data);

        return $category->fresh();
    }

    public function deleteCategory(\App\Models\ProductCategory $category): void
    {
        $category->delete();
    }
}
