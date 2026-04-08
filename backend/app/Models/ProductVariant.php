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
        'base_curve',
        'diameter',
        'price_adjustment',
        'is_default',
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
            'price_adjustment' => 'decimal:2',
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

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    /**
     * Selling unit price: base product price plus variant adjustment.
     */
    public function unitPrice(): string
    {
        $base = (string) $this->product->price;
        $adj = (string) $this->price_adjustment;

        return bcadd($base, $adj, 2);
    }
}
