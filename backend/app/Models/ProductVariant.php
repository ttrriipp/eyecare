<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color',
        'frame_size',
        'material',
        'lens_type',
        'power',
        'duration',
        'base_curve',
        'diameter',
        'price',
        'cost_per_unit',
        'is_default',
        'ar_model_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant) {
            if (! filled($variant->sku)) {
                $variant->sku = static::makeUniqueSku();
            }
        });
    }

    /**
     * Unique sellable-unit code (format PRD-XXXXXXXX). Stored on variant, not mass-assignable.
     */
    public static function makeUniqueSku(): string
    {
        do {
            $sku = 'PRD-'.Str::upper(Str::random(8));
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_per_unit' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_variant_id');
    }

    /**
     * Images scoped to this variant only (excludes product-wide shared images).
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id')->orderBy('sort_order');
    }

    /**
     * First image for this variant: variant-specific row if any, otherwise first shared product image.
     */
    public function firstGalleryImage(): ?ProductImage
    {
        $own = $this->images()->orderBy('sort_order')->first();
        if ($own) {
            return $own;
        }

        $product = $this->relationLoaded('product') ? $this->product : $this->product()->first();

        return $product?->sharedImages()->orderBy('sort_order')->first();
    }

    /** @deprecated Use {@see firstGalleryImage()} for thumbnails; kept for eager-loading variant-only rows */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_variant_id')->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    /**
     * Selling unit price for this SKU.
     */
    public function unitPrice(): string
    {
        return (string) $this->price;
    }
}
