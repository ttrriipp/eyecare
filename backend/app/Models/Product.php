<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'name',
        'description',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Display / list price from the default variant (sellable unit price lives on variants).
     */
    protected function price(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('defaultVariant')) {
                    $p = $this->defaultVariant?->price;

                    return $p !== null ? (string) $p : null;
                }

                $raw = $this->defaultVariant()->value('price');

                return $raw !== null ? (string) $raw : null;
            },
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * All catalog images for this product. Rows with null `product_variant_id` are shared across variants;
     * non-null rows are variant-specific.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Product-wide gallery images (shown for every variant unless overridden by variant-specific images).
     */
    public function sharedImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->whereNull('product_variant_id')->orderBy('sort_order');
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
        $query->whereHas('variants', function (Builder $q) use ($min, $max) {
            if ($min !== null) {
                $q->where('price', '>=', $min);
            }
            if ($max !== null) {
                $q->where('price', '<=', $max);
            }
        });

        return $query;
    }
}
