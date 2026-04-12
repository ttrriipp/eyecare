<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPaymentHistory extends Model
{
    public const ACTION_PAYMENT_RECORDED = 'payment_recorded';

    public const ACTION_VOIDED = 'voided';

    public const ACTION_REFUNDED = 'refunded';

    public const ACTION_UPDATED_FROM_ORDER_CANCELLATION = 'updated_from_order_cancellation';

    public $timestamps = false;

    protected $fillable = [
        'bill_id',
        'actor_user_id',
        'action',
        'amount',
        'payment_method',
        'from_payment_status',
        'to_payment_status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'from_payment_status' => PaymentStatus::class,
            'to_payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BillingPaymentHistory $row): void {
            if ($row->created_at === null) {
                $row->created_at = now();
            }
        });
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
