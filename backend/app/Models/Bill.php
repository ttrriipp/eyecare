<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'appointment_id',
        'invoice_number',
        'amount',
        'payment_status',
        'payment_method',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Appointment relationship will be added when scheduling module is built

    // ── Scopes ───────────────────────────────────────────────

    public function scopeByPaymentStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    public function scopeForOrder(Builder $query, int $orderId): Builder
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhereHas('order', function (Builder $oq) use ($term) {
                    $oq->where('order_number', 'like', "%{$term}%");
                });
        });
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === PaymentStatus::Unpaid;
    }
}
