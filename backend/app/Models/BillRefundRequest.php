<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\RefundRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillRefundRequest extends Model
{
    protected $fillable = [
        'bill_id',
        'requested_by_user_id',
        'status',
        'refund_amount',
        'refund_method',
        'note',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => RefundRequestStatus::class,
            'refund_amount' => 'decimal:2',
            'refund_method' => PaymentMethod::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === RefundRequestStatus::Pending;
    }
}
