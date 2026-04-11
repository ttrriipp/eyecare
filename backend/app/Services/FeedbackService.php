<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\FeedbackApprovalStatus;
use App\Enums\FeedbackType;
use App\Enums\OrderStatus;
use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    /**
     * Paginate all feedback for staff/admin (moderation queue + full history).
     */
    public function paginateForStaff(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Feedback::query()
            ->with(['user', 'product.category', 'moderator', 'appointment', 'approvalReviewer']);

        if (! empty($filters['product_id'])) {
            $query->forProduct((int) $filters['product_id']);
        }

        if (! empty($filters['feedback_type'])) {
            $type = FeedbackType::tryFrom((string) $filters['feedback_type']);
            if ($type !== null) {
                $query->ofType($type);
            }
        }

        if (! empty($filters['approval_status'])) {
            $status = FeedbackApprovalStatus::tryFrom((string) $filters['approval_status']);
            if ($status !== null) {
                $query->where('approval_status', $status);
            }
        }

        if (! empty($filters['rating'])) {
            $query->byRating((int) $filters['rating']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('variants', function ($v) use ($search) {
                            $v->where('sku', 'like', '%'.$search.'%');
                        });
                })
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhere('comment', 'like', '%'.$search.'%');
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Public product reviews (approved, not staff-hidden).
     */
    public function listForProduct(int $productId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Feedback::query()
            ->with(['user'])
            ->forProduct($productId)
            ->ofType(FeedbackType::Product)
            ->publicListing();

        if (! empty($filters['rating'])) {
            $query->byRating((int) $filters['rating']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Create a product review (pending until staff approves).
     */
    public function create(int $userId, array $data): Feedback
    {
        $productId = (int) $data['product_id'];
        if (! $this->hasCompletedPurchase($userId, $productId)) {
            throw ValidationException::withMessages([
                'product_id' => 'You can only review products from completed orders.',
            ]);
        }

        $existing = Feedback::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->ofType(FeedbackType::Product)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'product_id' => 'You have already reviewed this product.',
            ]);
        }

        $feedback = Feedback::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'feedback_type' => FeedbackType::Product,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => true,
            'is_visible' => true,
            'approval_status' => FeedbackApprovalStatus::Approved,
        ]);

        return $feedback->load('user');
    }

    /**
     * General service / clinic feedback (not tied to a product).
     */
    public function createServiceFeedback(int $userId, array $data): Feedback
    {
        if (! $this->canSubmitServiceOrAppointmentFeedback($userId)) {
            throw ValidationException::withMessages([
                'feedback' => 'You can submit this feedback after at least one completed visit or completed order.',
            ]);
        }

        $feedback = Feedback::create([
            'user_id' => $userId,
            'product_id' => null,
            'appointment_id' => null,
            'feedback_type' => FeedbackType::Service,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => false,
            'is_visible' => true,
            'approval_status' => FeedbackApprovalStatus::Approved,
        ]);

        return $feedback->load('user');
    }

    /**
     * Feedback for a specific completed appointment.
     */
    public function createAppointmentFeedback(int $userId, int $appointmentId, array $data): Feedback
    {
        $appointment = Appointment::query()->findOrFail($appointmentId);

        if ((int) $appointment->user_id !== $userId) {
            throw ValidationException::withMessages([
                'appointment_id' => 'This appointment does not belong to you.',
            ]);
        }

        if ($appointment->status !== AppointmentStatus::Completed) {
            throw ValidationException::withMessages([
                'appointment_id' => 'You can only review completed appointments.',
            ]);
        }

        $existing = Feedback::query()
            ->where('user_id', $userId)
            ->where('appointment_id', $appointmentId)
            ->ofType(FeedbackType::Appointment)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'appointment_id' => 'You have already reviewed this appointment.',
            ]);
        }

        $feedback = Feedback::create([
            'user_id' => $userId,
            'product_id' => null,
            'appointment_id' => $appointmentId,
            'feedback_type' => FeedbackType::Appointment,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => false,
            'is_visible' => true,
            'approval_status' => FeedbackApprovalStatus::Approved,
        ]);

        return $feedback->load(['user', 'appointment']);
    }

    /**
     * Update a review (customer). Re-queues for approval when content changes.
     */
    public function update(Feedback $feedback, array $data): Feedback
    {
        if ($feedback->feedback_type === FeedbackType::Product) {
            if ($feedback->product_id === null || ! $this->hasCompletedPurchase($feedback->user_id, (int) $feedback->product_id)) {
                throw ValidationException::withMessages([
                    'product_id' => 'You can only review products from completed orders.',
                ]);
            }
        }

        $payload = collect($data)->only(['rating', 'comment'])->filter(fn ($v) => $v !== null)->all();

        $feedback->update(array_merge($payload, [
            'approval_status' => FeedbackApprovalStatus::Approved,
            'approval_reviewed_at' => null,
            'approval_reviewed_by' => null,
            'rejection_reason' => null,
        ]));

        return $feedback->fresh(['user', 'appointment']);
    }

    public function approve(Feedback $feedback, int $reviewerId): Feedback
    {
        $feedback->update([
            'approval_status' => FeedbackApprovalStatus::Approved,
            'approval_reviewed_at' => now(),
            'approval_reviewed_by' => $reviewerId,
            'rejection_reason' => null,
            'is_visible' => true,
        ]);

        return $feedback->fresh(['user', 'product.category', 'moderator', 'appointment', 'approvalReviewer']);
    }

    public function reject(Feedback $feedback, int $reviewerId, ?string $reason): Feedback
    {
        $feedback->update([
            'approval_status' => FeedbackApprovalStatus::Rejected,
            'approval_reviewed_at' => now(),
            'approval_reviewed_by' => $reviewerId,
            'rejection_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
        ]);

        return $feedback->fresh(['user', 'product.category', 'moderator', 'appointment', 'approvalReviewer']);
    }

    public function canUserReviewProduct(int $userId, int $productId): bool
    {
        return $this->hasCompletedPurchase($userId, $productId);
    }

    public function canSubmitServiceFeedback(int $userId): bool
    {
        return $this->canSubmitServiceOrAppointmentFeedback($userId);
    }

    public function getUserFeedbackForProduct(int $userId, int $productId): ?Feedback
    {
        return Feedback::query()
            ->with(['user'])
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->ofType(FeedbackType::Product)
            ->first();
    }

    public function respond(Feedback $feedback, int $responderUserId, ?string $reply): Feedback
    {
        $trimmed = $reply !== null ? trim($reply) : '';

        if ($trimmed === '') {
            $feedback->update([
                'admin_reply' => null,
                'moderated_by' => null,
                'moderated_at' => null,
            ]);
        } else {
            $feedback->update([
                'admin_reply' => $trimmed,
                'moderated_by' => $responderUserId,
                'moderated_at' => now(),
            ]);
        }

        return $feedback->fresh(['user', 'product.category', 'moderator']);
    }

    public function setPublicVisibility(Feedback $feedback, bool $visible, int $moderatorId): Feedback
    {
        $feedback->update([
            'is_visible' => $visible,
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
        ]);

        return $feedback->fresh(['user', 'product.category', 'moderator', 'appointment', 'approvalReviewer']);
    }

    public function delete(Feedback $feedback): void
    {
        $feedback->delete();
    }

    public function averageRating(int $productId): ?float
    {
        $avg = Feedback::query()
            ->forProduct($productId)
            ->ofType(FeedbackType::Product)
            ->publicListing()
            ->avg('rating');

        return $avg ? round((float) $avg, 1) : null;
    }

    private function canSubmitServiceOrAppointmentFeedback(int $userId): bool
    {
        return $this->hasAnyCompletedOrder($userId) || $this->hasCompletedAppointment($userId);
    }

    private function hasAnyCompletedOrder(int $userId): bool
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('status', OrderStatus::Completed)
            ->exists();
    }

    private function hasCompletedAppointment(int $userId): bool
    {
        return Appointment::query()
            ->where('user_id', $userId)
            ->where('status', AppointmentStatus::Completed)
            ->exists();
    }

    private function hasCompletedPurchase(int $userId, int $productId): bool
    {
        return OrderItem::query()
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('status', OrderStatus::Completed);
            })
            ->whereHas('productVariant', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();
    }
}
