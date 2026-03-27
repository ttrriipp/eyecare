<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'walk_in_name',
        'walk_in_phone',
        'order_number',
        'status',
        'total_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_amount' => 'decimal:2',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeByStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('walk_in_name', 'like', "%{$term}%")
                ->orWhere('walk_in_phone', 'like', "%{$term}%")
                ->orWhereHas('user', function (Builder $uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isWalkIn(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Customers can cancel before ReadyForPickup.
     * Staff/admin can cancel anytime (as long as order isn't already completed/cancelled).
     */
    public function canBeCancelledBy(User $user): bool
    {
        if ($this->status === OrderStatus::Completed || $this->status === OrderStatus::Cancelled) {
            return false;
        }

        if ($user->isAdminOrStaff()) {
            return true;
        }

        // Customers can only cancel before ReadyForPickup
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Confirmed]);
    }
}
