<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'description',
        'price',
        'cost_per_unit',
        'brand',
        'is_active',
    ];

    /**
     * Convenience accessor: default variant's SKU (sellable units are variants).
     */
    public function getSkuAttribute(): ?string
    {
        return $this->defaultVariant?->sku;
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Images for the default variant only (catalog / product detail convenience).
     * Per-variant images live on {@see ProductVariant::images()}.
     */
    public function images(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductImage::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
            'id',
            'id',
        )->where('product_variants.is_default', true)
            ->orderBy('product_images.sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    /**
     * Inventory rows for all variants (one row per variant).
     */
    public function variantInventories(): HasManyThrough
    {
        return $this->hasManyThrough(Inventory::class, ProductVariant::class, 'product_id', 'product_variant_id');
    }

    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class, 'product_id', 'product_variant_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%")
                ->orWhereHas('variants', function (Builder $vq) use ($term) {
                    $vq->where('sku', 'like', "%{$term}%");
                });
        });
    }

    public function scopePriceRange(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }
}
