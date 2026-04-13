<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'quantity_before',
        'quantity_after',
        'delta',
        'adjustment_type',
        'reason',
        'adjusted_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'delta' => 'integer',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
