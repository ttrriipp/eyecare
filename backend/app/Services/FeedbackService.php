<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Feedback;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    /**
     * Paginate all reviews for staff/admin (product + customer visible).
     */
    public function paginateForStaff(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Feedback::query()
            ->with(['user', 'product.category']);

        if (! empty($filters['product_id'])) {
            $query->forProduct((int) $filters['product_id']);
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
     * List reviews for a product with pagination.
     */
    public function listForProduct(int $productId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Feedback::query()
            ->with(['user'])
            ->forProduct($productId)
            ->visible();

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
            'is_verified_purchase' => $this->hasCompletedPurchase($userId, (int) $data['product_id']),
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

    /**
     * Verify if customer has at least one completed order containing the product.
     */
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
