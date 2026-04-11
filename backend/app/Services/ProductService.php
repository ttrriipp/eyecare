<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
        $query = Product::with([
            'category',
            'images',
            'defaultVariant.product',
            'defaultVariant.images',
            'sharedImages',
            'variants' => function ($q): void {
                $q->select([
                    'product_variants.id',
                    'product_variants.product_id',
                    'product_variants.ar_model_url',
                    'product_variants.is_default',
                    'product_variants.is_active',
                ])
                    ->with('inventory');
            },
        ])
            ->withAvg('feedbacks as average_rating', 'rating')
            ->withCount(['feedbacks as reviews_count']);

        $this->applyProductActiveScope($query, $filters);

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

        if ($sortBy === 'price') {
            $query->orderBy(
                ProductVariant::query()
                    ->select('price')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('product_variants.is_default', true)
                    ->limit(1),
                $sortDir
            );
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::with([
            'category',
            'images',
            'sharedImages',
            'defaultVariant.product',
            'defaultVariant.images',
            'defaultVariant.inventory',
            'variants.images',
            'variants.product',
            'variants.inventory',
        ])
            ->withAvg('feedbacks as average_rating', 'rating')
            ->withCount(['feedbacks as reviews_count'])
            ->findOrFail($id);
    }

    public function create(array $data, bool $ensureDefaultVariant = true): Product
    {
        unset($data['ar_model_url']);
        $hasCost = array_key_exists('cost_per_unit', $data);
        $cost = Arr::pull($data, 'cost_per_unit');
        $price = Arr::pull($data, 'price');

        $product = Product::create($data);
        if (! $ensureDefaultVariant) {
            return $product->load(['category', 'images', 'sharedImages', 'defaultVariant.product', 'defaultVariant.images', 'variants.images', 'variants.product']);
        }

        $variant = $this->ensureDefaultVariantIfMissing($product);

        $variantUpdates = [];
        if ($price !== null) {
            $variantUpdates['price'] = $price;
        }
        if ($hasCost) {
            $variantUpdates['cost_per_unit'] = $cost;
        }
        if ($variantUpdates !== []) {
            $variant->update($variantUpdates);
        }

        return $product->load(['category', 'images', 'sharedImages', 'defaultVariant.product', 'defaultVariant.images', 'variants.images', 'variants.product']);
    }

    public function update(Product $product, array $data): Product
    {
        unset($data['ar_model_url']);
        $hasCost = array_key_exists('cost_per_unit', $data);
        $cost = Arr::pull($data, 'cost_per_unit');
        $price = Arr::pull($data, 'price');

        $product->update($data);

        $variantUpdates = [];
        if ($price !== null) {
            $variantUpdates['price'] = $price;
        }
        if ($hasCost) {
            $variantUpdates['cost_per_unit'] = $cost;
        }
        if ($variantUpdates !== []) {
            $product->loadMissing('defaultVariant');
            $product->defaultVariant?->update($variantUpdates);
        }

        return $product->fresh(['category', 'images', 'sharedImages', 'defaultVariant.product', 'defaultVariant.images', 'variants.images', 'variants.product']);
    }

    /**
     * Set AR model URL on the product's default variant (e.g. legacy API / web forms that still post `ar_model_url` on the product).
     *
     * @param  mixed  $url
     */
    public function applyArModelToDefaultVariant(Product $product, $url): void
    {
        $product->loadMissing('defaultVariant');
        $variant = $product->defaultVariant;
        if (! $variant) {
            return;
        }

        $this->updateVariant($variant, ['ar_model_url' => $url]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeVariantArModelUrl(Product $product, array $data): array
    {
        if (! array_key_exists('ar_model_url', $data)) {
            return $data;
        }

        $product->loadMissing('category');
        if (! $product->category?->has_ar_support) {
            $data['ar_model_url'] = null;
        } else {
            $v = $data['ar_model_url'];
            $data['ar_model_url'] = filled($v) ? trim((string) $v) : null;
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

        $product->loadMissing('images');
        foreach ($product->images as $image) {
            $this->deletePhysicalFile($image->image_url);
        }

        $product->delete();
    }

    public function addImage(Product $product, ?ProductVariant $variant, string $imageUrl, int $sortOrder = 0): ProductImage
    {
        return ProductImage::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'image_url' => $imageUrl,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Persist multiple catalog image uploads for a variant (e.g. Livewire admin flows).
     *
     * @param  array<int, UploadedFile>  $uploads
     */
    public function attachUploadedImagesToVariant(ProductVariant $variant, array $uploads): void
    {
        $variant->loadMissing(['images', 'product']);
        $product = $variant->product;
        $nextOrder = $variant->images->isEmpty() ? 0 : ($variant->images->max('sort_order') + 1);

        foreach ($uploads as $upload) {
            $url = $this->storePublicCatalogImage($upload);
            $this->addImage($product, $variant, $url, $nextOrder++);
        }
    }

    /**
     * Save an upload under public/images/products and return its public URL.
     * Uses {@see copy()} instead of move/rename so Livewire temp files work when the PHP temp
     * directory and public/ are on different Windows volumes.
     */
    public function storePublicCatalogImage(UploadedFile $file): string
    {
        $dir = public_path(implode(DIRECTORY_SEPARATOR, ['images', 'products']));
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if ($ext === '') {
            $ext = match ($file->getMimeType()) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                default => 'jpg',
            };
        }

        $filename = Str::uuid()->toString().'.'.$ext;
        $target = $dir.DIRECTORY_SEPARATOR.$filename;

        $source = $file->getRealPath();
        if ($source === false || ! is_readable($source)) {
            $source = $file->getPathname();
        }
        if (! is_readable($source)) {
            throw ValidationException::withMessages([
                'image' => __('Could not read the uploaded file. Please try again.'),
            ]);
        }

        if (! @copy($source, $target)) {
            throw ValidationException::withMessages([
                'image' => __('Could not save the image. Check that the server can write to public/images/products.'),
            ]);
        }

        return asset('images/products/'.$filename);
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
     * Delete a category, guarded against system categories and categories with products.
     *
     * Aborts with 403 if the category is a system category.
     * Aborts with 409 if the category has any products (including soft-deleted).
     */
    public function deleteCategory(\App\Models\ProductCategory $category): void
    {
        if ($category->is_system) {
            abort(403, 'System categories cannot be deleted.');
        }

        $hasProducts = $category->products()->withTrashed()->exists();

        if ($hasProducts) {
            abort(409, 'Reassign or delete all products in this category first.');
        }

        $category->delete();
    }

    /**
     * Create a variant for a product and seed its inventory row.
     */
    /**
     * @param  array<string, mixed>  $inventoryExtras  Optional keys: batch_number, expires_at (date string), reorder_quantity
     */
    public function createVariant(
        Product $product,
        array $variantData,
        int $initialStock = 0,
        int $reorderLevel = 5,
        array $inventoryExtras = [],
    ): ProductVariant {
        $product->loadMissing('category');
        $variantData = $this->normalizeVariantArModelUrl($product, $variantData);

        $variant = $product->variants()->create($variantData);

        $batch = $inventoryExtras['batch_number'] ?? null;
        $expires = $inventoryExtras['expires_at'] ?? null;
        $reorderQty = (int) ($inventoryExtras['reorder_quantity'] ?? 0);

        Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => $initialStock,
            'reorder_level' => $reorderLevel,
            'reorder_quantity' => $reorderQty,
            'batch_number' => filled($batch) ? $batch : null,
            'expires_at' => filled($expires) ? $expires : null,
        ]);

        return $variant->load('inventory');
    }

    /**
     * Ensure a default sellable variant exists (with an inventory row).
     *
     * Livewire admin product creation skips this by adding explicit variants after
     * {@see create()}. Legacy web/API flows and seeders call this when a product
     * must have exactly one default unit.
     */
    public function ensureDefaultVariantIfMissing(Product $product): ProductVariant
    {
        if ($product->variants()->exists()) {
            $product->loadMissing('defaultVariant');

            return $product->defaultVariant
                ?? $product->variants()->orderBy('id')->firstOrFail();
        }

        return $this->createVariant(
            $product,
            [
                'is_default' => true,
                'price' => '0.01',
            ],
            0,
            0,
        );
    }

    /**
     * Update a variant's fields.
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->loadMissing('product');
        $data = $this->normalizeVariantArModelUrl($variant->product, $data);
        $variant->update($data);

        return $variant->fresh('inventory');
    }

    /**
     * Turn a variant on or off for the storefront. At least one variant must stay active.
     * Deactivating the default SKU promotes another active variant to default when possible.
     */
    public function setVariantIsActive(ProductVariant $variant, bool $active): void
    {
        $variant->loadMissing('product');
        $product = $variant->product;

        if ($active) {
            $variant->update(['is_active' => true]);
            $product->refresh();
            $product->loadMissing('defaultVariant');
            if ($product->defaultVariant && ! $product->defaultVariant->is_active) {
                $this->setDefaultVariant($variant->fresh());
            }

            return;
        }

        if (! $variant->is_active) {
            return;
        }

        $otherActiveExists = $product->variants()
            ->whereKeyNot($variant->id)
            ->where('is_active', true)
            ->exists();

        if (! $otherActiveExists) {
            throw ValidationException::withMessages([
                'variant' => __('At least one variant must stay active.'),
            ]);
        }

        if ($variant->is_default) {
            $next = $product->variants()
                ->whereKeyNot($variant->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
            if ($next) {
                $this->setDefaultVariant($next);
            }
        }

        $variant->update(['is_active' => false]);
    }

    /**
     * Mark this variant as the product's default and clear the flag on siblings.
     */
    public function setDefaultVariant(ProductVariant $variant): void
    {
        $variant->loadMissing('product');
        ProductVariant::query()
            ->where('product_id', $variant->product_id)
            ->whereKeyNot($variant->id)
            ->update(['is_default' => false]);
        $variant->update(['is_default' => true]);
    }

    /**
     * If no variant is marked default, set the lowest-id variant as default.
     */
    public function ensureProductHasDefaultVariant(Product $product): void
    {
        $product->load('variants');
        if ($product->variants->contains(fn (ProductVariant $v) => $v->is_default)) {
            return;
        }
        $first = $product->variants()->orderBy('id')->first();
        if ($first) {
            $first->update(['is_default' => true]);
        }
    }

    /**
     * Update inventory batch / expiry for a variant (used when category tracks expiry).
     *
     * @param  array<string, mixed>|null  $extras  Keys: batch_number, expires_at (Y-m-d). If category does not require tracking, batch/expiry are cleared.
     */
    public function syncVariantInventoryExtras(ProductVariant $variant, ?array $extras): void
    {
        $variant->loadMissing('product.category', 'inventory');
        $inv = $variant->inventory;
        if (! $inv) {
            return;
        }

        $requires = (bool) $variant->product->category?->requires_expiry_tracking;

        if (! $requires) {
            $inv->update([
                'batch_number' => null,
                'expires_at' => null,
            ]);

            return;
        }

        $inv->update([
            'batch_number' => filled($extras['batch_number'] ?? null) ? trim((string) $extras['batch_number']) : null,
            'expires_at' => filled($extras['expires_at'] ?? null) ? $extras['expires_at'] : null,
        ]);
    }

    /**
     * Delete a variant after checking it has no active orders.
     * Also zeros out and records a final inventory adjustment.
     *
     * @throws ValidationException if variant has active orders
     */
    public function deleteVariant(ProductVariant $variant, ?int $actorId = null): void
    {
        $hasActiveOrders = $variant->orderItems()
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', [
                OrderStatus::Completed->value,
                OrderStatus::Cancelled->value,
            ]))
            ->exists();

        if ($hasActiveOrders) {
            throw ValidationException::withMessages([
                'variant' => __('Cannot delete this variant because it has active orders.'),
            ]);
        }

        $inventory = $variant->inventory;
        if ($inventory && $inventory->quantity > 0) {
            $inventory->adjustments()->create([
                'quantity_before' => $inventory->quantity,
                'quantity_after' => 0,
                'delta' => -$inventory->quantity,
                'adjustment_type' => 'subtract',
                'reason' => 'variant_removed',
                'adjusted_by' => $actorId,
            ]);
        }

        $inventory?->delete();

        $variant->loadMissing('images');
        foreach ($variant->images as $image) {
            $this->deletePhysicalFile($image->image_url);
        }

        $productId = $variant->product_id;
        $variant->delete();

        $product = Product::find($productId);
        if ($product) {
            $this->ensureProductHasDefaultVariant($product);
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyProductActiveScope(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? null;

        if (is_string($status) && in_array($status, ['active', 'inactive', 'all'], true)) {
            match ($status) {
                'active' => $query->active(),
                'inactive' => $query->where('is_active', false),
                'all' => null,
            };

            return;
        }

        if ($filters['include_inactive'] ?? false) {
            return;
        }

        $query->active();
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
