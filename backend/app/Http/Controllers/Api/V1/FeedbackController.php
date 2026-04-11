<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ApproveFeedbackApiRequest;
use App\Http\Requests\Api\V1\RejectFeedbackApiRequest;
use App\Http\Requests\Api\V1\StoreAppointmentFeedbackRequest;
use App\Http\Requests\Api\V1\StoreFeedbackRequest;
use App\Http\Requests\Api\V1\StoreServiceFeedbackRequest;
use App\Http\Requests\Api\V1\UpdateFeedbackRequest;
use App\Http\Resources\V1\FeedbackResource;
use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\Product;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Submit a product review (customer only). Pending until approved.
     */
    public function store(StoreFeedbackRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['product_id'] = $product->id;

        $feedback = $this->feedbackService->create($request->user()->id, $data);

        return response()->json([
            'message' => 'Review submitted. It will appear after staff approval.',
            'feedback' => new FeedbackResource($feedback),
        ], 201);
    }

    /**
     * Submit general service / clinic feedback (customer only).
     */
    public function storeService(StoreServiceFeedbackRequest $request): JsonResponse
    {
        $feedback = $this->feedbackService->createServiceFeedback(
            $request->user()->id,
            $request->validated(),
        );

        return response()->json([
            'message' => 'Thank you. Your feedback was submitted for review.',
            'feedback' => new FeedbackResource($feedback),
        ], 201);
    }

    /**
     * Submit feedback for a completed appointment (customer only).
     */
    public function storeAppointment(StoreAppointmentFeedbackRequest $request, Appointment $appointment): JsonResponse
    {
        $feedback = $this->feedbackService->createAppointmentFeedback(
            $request->user()->id,
            $appointment->id,
            $request->validated(),
        );

        return response()->json([
            'message' => 'Thank you. Your feedback was submitted for review.',
            'feedback' => new FeedbackResource($feedback),
        ], 201);
    }

    /**
     * Update own feedback (customer only). Re-queues for approval.
     */
    public function update(UpdateFeedbackRequest $request, Feedback $feedback): JsonResponse
    {
        $feedback = $this->feedbackService->update($feedback, $request->validated());

        return response()->json([
            'message' => 'Review updated. It will appear after staff approval.',
            'feedback' => new FeedbackResource($feedback),
        ]);
    }

    /**
     * Delete own feedback (customer) or any feedback (admin).
     */
    public function destroy(Request $request, Feedback $feedback): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $this->feedbackService->delete($feedback);

            return response()->json([
                'message' => 'Review deleted successfully.',
            ]);
        }

        if ($user->isCustomer() && (int) $feedback->user_id === (int) $user->id) {
            $this->feedbackService->delete($feedback);

            return response()->json([
                'message' => 'Your review was deleted.',
            ]);
        }

        abort(403, 'You cannot delete this review.');
    }

    public function approve(ApproveFeedbackApiRequest $request, Feedback $feedback): JsonResponse
    {
        $feedback = $this->feedbackService->approve($feedback, $request->user()->id);

        return response()->json([
            'message' => 'Feedback approved.',
            'feedback' => new FeedbackResource($feedback),
        ]);
    }

    public function reject(RejectFeedbackApiRequest $request, Feedback $feedback): JsonResponse
    {
        $feedback = $this->feedbackService->reject(
            $feedback,
            $request->user()->id,
            $request->validated('rejection_reason'),
        );

        return response()->json([
            'message' => 'Feedback rejected.',
            'feedback' => new FeedbackResource($feedback),
        ]);
    }
}
