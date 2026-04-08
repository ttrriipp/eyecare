<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /**
     * Allowed columns for sorting to prevent arbitrary column injection.
     */
    private const ALLOWED_SORT_COLUMNS = [
        'name',
        'price',
        'brand',
        'created_at',
        'updated_at',
    ];

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['category', 'supplier', 'images', 'defaultVariant.product'])
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

        $sortBy = in_array($filters['sort_by'] ?? '', self::ALLOWED_SORT_COLUMNS, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::with(['category', 'supplier', 'images', 'defaultVariant.product', 'variants.product'])
            ->withAvg('feedbacks as average_rating', 'rating')
            ->withCount(['feedbacks as reviews_count'])
            ->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $product = Product::create($this->normalizeArModelUrlForCategory($data));

        return $product->load(['category', 'supplier', 'images', 'defaultVariant.product', 'variants.product']);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($this->normalizeArModelUrlForCategory($data, $product));

        return $product->fresh(['category', 'supplier', 'images', 'defaultVariant.product', 'variants.product']);
    }

    /**
     * Clear AR model URL when the target category does not support virtual try-on.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeArModelUrlForCategory(array $data, ?Product $existing = null): array
    {
        $categoryId = $data['category_id'] ?? $existing?->category_id;
        if ($categoryId === null) {
            return $data;
        }

        $category = ProductCategory::query()->find($categoryId);
        if ($category && ! $category->has_ar_support) {
            $data['ar_model_url'] = null;
        }

        return $data;
    }

    /**
     * Delete a product after checking for active orders.
     * Also cleans up physical image files from disk.
     *
     * @throws ValidationException if product has active (non-completed/cancelled) orders
     */
    public function delete(Product $product): void
    {
        $hasActiveOrders = $product->orderItems()
            ->whereHas('order', function ($query) {
                $query->whereNotIn('status', [
                    OrderStatus::Completed->value,
                    OrderStatus::Cancelled->value,
                ]);
            })
            ->exists();

        if ($hasActiveOrders) {
            throw ValidationException::withMessages([
                'product' => __('Cannot delete this product because it has active orders.'),
            ]);
        }

        // Clean up physical image files
        foreach ($product->images as $image) {
            $this->deletePhysicalFile($image->image_url);
        }

        $product->delete();
    }

    public function addImage(Product $product, string $imageUrl, int $sortOrder = 0): ProductImage
    {
        return $product->images()->create([
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Delete a product image and its physical file from disk.
     */
    public function deleteImage(ProductImage $image): void
    {
        $this->deletePhysicalFile($image->image_url);
        $image->delete();
    }

    public function listCategories(): Collection
    {
        return \App\Models\ProductCategory::orderBy('name')->get();
    }

    public function listSuppliers(): Collection
    {
        return Supplier::query()->active()->orderBy('name')->get();
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

    /**
     * Delete a category, guarded against categories that still contain products.
     *
     * @throws ValidationException if category has products
     */
    public function deleteCategory(\App\Models\ProductCategory $category): void
    {
        $productsCount = $category->products()->count();

        if ($productsCount > 0) {
            throw ValidationException::withMessages([
                'category' => __('Cannot delete this category because it is currently used by :count product(s). Reassign or remove those products first.', [
                    'count' => $productsCount,
                ]),
            ]);
        }

        $category->delete();
    }

    /**
     * Attempt to delete a physical image file from the public directory.
     */
    private function deletePhysicalFile(string $imageUrl): void
    {
        // Extract relative path from the full URL
        $parsed = parse_url($imageUrl, PHP_URL_PATH);

        if (! $parsed) {
            return;
        }

        $filePath = public_path($parsed);

        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }
}
