<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'appointment_id',
        'invoice_number',
        'official_receipt_number',
        'amount',
        'amount_paid',
        'balance_due',
        'payment_status',
        'payment_method',
        'collected_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(BillingPaymentHistory::class)->orderByDesc('created_at');
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
            $q->where('invoice_number', 'like', "%{$term}%");

            if (Schema::hasColumn($q->getModel()->getTable(), 'official_receipt_number')) {
                $q->orWhere('official_receipt_number', 'like', "%{$term}%");
            }

            $q->orWhereHas('order', function (Builder $oq) use ($term) {
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

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === PaymentStatus::PartiallyPaid;
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->payment_status === PaymentStatus::PartiallyRefunded;
    }
}
