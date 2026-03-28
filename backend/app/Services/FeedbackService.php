<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    /**
     * List reviews for a product with pagination.
     */
    public function listForProduct(int $productId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Feedback::query()
            ->with(['user'])
            ->forProduct($productId);

        if (! empty($filters['rating'])) {
            $query->byRating((int) $filters['rating']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Create a review for a product.
     * Enforces one review per customer per product.
     */
    public function create(int $userId, array $data): Feedback
    {
        $existing = Feedback::where('user_id', $userId)
            ->where('product_id', $data['product_id'])
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'product_id' => 'You have already reviewed this product.',
            ]);
        }

        $feedback = Feedback::create([
            'user_id' => $userId,
            'product_id' => $data['product_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return $feedback->load('user');
    }

    /**
     * Update a review.
     */
    public function update(Feedback $feedback, array $data): Feedback
    {
        $feedback->update($data);

        return $feedback->fresh(['user']);
    }

    /**
     * Delete a review (admin only).
     */
    public function delete(Feedback $feedback): void
    {
        $feedback->delete();
    }

    /**
     * Get average rating for a product.
     */
    public function averageRating(int $productId): ?float
    {
        $avg = Feedback::forProduct($productId)->avg('rating');

        return $avg ? round((float) $avg, 1) : null;
    }
}
