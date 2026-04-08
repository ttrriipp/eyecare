<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_variant_id',
        'quantity',
        'reorder_level',
        'reorder_quantity',
        'batch_number',
        'expires_at',
        'storage_location',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'reorder_level' => 'integer',
            'reorder_quantity' => 'integer',
            'expires_at' => 'date',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class)->latest();
    }

    /**
     * Parent product (via variant). Eager-load `productVariant.product` when listing.
     */
    protected function product(): Attribute
    {
        return Attribute::get(fn (): ?Product => $this->productVariant?->product);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }

    public function isInStock(int $amount = 1): bool
    {
        return $this->quantity >= $amount;
    }
}
