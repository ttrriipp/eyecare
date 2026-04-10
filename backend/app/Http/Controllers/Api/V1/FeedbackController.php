<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFeedbackRequest;
use App\Http\Requests\Api\V1\UpdateFeedbackRequest;
use App\Http\Resources\V1\FeedbackResource;
use App\Models\Feedback;
use App\Models\Product;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    ) {}

    /**
     * List reviews for a product.
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        $filters = $request->only(['rating', 'sort_by', 'sort_dir']);
        $user = $request->user();

        $feedbacks = $this->feedbackService->listForProduct(
            productId: $product->id,
            filters: $filters,
            perPage: $request->integer('per_page', 15),
        );

        $myFeedback = null;
        $canReview = false;
        if ($user !== null && $user->isCustomer()) {
            $myFeedback = $this->feedbackService->getUserFeedbackForProduct($user->id, $product->id);
            $canReview = $myFeedback !== null
                || $this->feedbackService->canUserReviewProduct($user->id, $product->id);
        }

        return response()->json([
            'data' => FeedbackResource::collection($feedbacks),
            'average_rating' => $this->feedbackService->averageRating($product->id),
            'can_review' => $canReview,
            'my_feedback' => $myFeedback ? new FeedbackResource($myFeedback) : null,
            'meta' => [
                'current_page' => $feedbacks->currentPage(),
                'last_page' => $feedbacks->lastPage(),
                'per_page' => $feedbacks->perPage(),
                'total' => $feedbacks->total(),
            ],
        ]);
    }

    /**
     * Submit a review for a product (customer only).
     */
    public function store(StoreFeedbackRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['product_id'] = $product->id;

        $feedback = $this->feedbackService->create($request->user()->id, $data);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'feedback' => new FeedbackResource($feedback),
        ], 201);
    }

    /**
     * Update own review (customer only).
     */
    public function update(UpdateFeedbackRequest $request, Feedback $feedback): JsonResponse
    {
        $feedback = $this->feedbackService->update($feedback, $request->validated());

        return response()->json([
            'message' => 'Review updated successfully.',
            'feedback' => new FeedbackResource($feedback),
        ]);
    }

    /**
     * Delete a review (admin only).
     */
    public function destroy(Request $request, Feedback $feedback): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Only administrators can delete reviews.');
        }

        $this->feedbackService->delete($feedback);

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }
}
