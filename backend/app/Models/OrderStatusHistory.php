<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    public const ACTION_CREATED = 'created';

    public const ACTION_STATUS_UPDATED = 'status_updated';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'actor_user_id',
        'action',
        'from_status',
        'to_status',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OrderStatusHistory $row): void {
            if ($row->created_at === null) {
                $row->created_at = now();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
