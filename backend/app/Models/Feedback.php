<?php

namespace App\Models;

use App\Enums\FeedbackApprovalStatus;
use App\Enums\FeedbackType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = [
        'user_id',
        'product_id',
        'appointment_id',
        'feedback_type',
        'rating',
        'comment',
        'is_verified_purchase',
        'is_visible',
        'approval_status',
        'approval_reviewed_at',
        'approval_reviewed_by',
        'rejection_reason',
        'admin_reply',
        'moderated_by',
        'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'feedback_type' => FeedbackType::class,
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'is_visible' => 'boolean',
            'approval_status' => FeedbackApprovalStatus::class,
            'approval_reviewed_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function approvalReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_reviewed_by');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /** Shown on public product pages and customer API lists. */
    public function scopeApprovedForPublic(Builder $query): Builder
    {
        return $query->where('approval_status', FeedbackApprovalStatus::Approved);
    }

    public function scopeOfType(Builder $query, FeedbackType $type): Builder
    {
        return $query->where('feedback_type', $type);
    }

    public function scopePublicListing(Builder $query): Builder
    {
        return $query->visible()->approvedForPublic();
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', FeedbackApprovalStatus::Pending);
    }
}
