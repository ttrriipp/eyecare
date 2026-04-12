<?php

namespace App\Http\Controllers;

use App\Http\Requests\RespondToFeedbackRequest;
use App\Http\Requests\SetFeedbackVisibilityRequest;
use App\Models\Feedback;
use App\Models\Product;
use App\Services\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search',
            'product_id',
            'rating',
            'feedback_type',
            'sort_by',
            'sort_dir',
        ]);

        $feedbacks = $this->feedbackService->paginateForStaff($filters, perPage: 15);

        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('feedback.index', [
            'feedbacks' => $feedbacks,
            'filters' => $filters,
            'products' => $products,
        ]);
    }

    public function respond(RespondToFeedbackRequest $request, Feedback $feedback): RedirectResponse
    {
        $this->feedbackService->respond(
            $feedback,
            $request->user()->id,
            $request->validated('admin_reply'),
        );

        $to = $request->validated('redirect_to');
        if (is_string($to) && $to !== '') {
            return redirect()->to($to)->with('status', __('Reply saved.'));
        }

        return redirect()
            ->route('feedbacks.index', $request->query())
            ->with('status', __('Reply saved.'));
    }

    public function setVisibility(SetFeedbackVisibilityRequest $request, Feedback $feedback): RedirectResponse
    {
        $visible = $request->boolean('is_visible');

        $this->feedbackService->setPublicVisibility(
            $feedback,
            $visible,
            $request->user()->id,
        );

        $message = $visible
            ? __('Review is visible on the product page again.')
            : __('Review hidden from the product page.');

        $to = $request->validated('redirect_to');
        if (is_string($to) && $to !== '') {
            return redirect()->to($to)->with('status', $message);
        }

        return redirect()
            ->route('feedbacks.index', $request->query())
            ->with('status', $message);
    }

    public function destroy(Request $request, Feedback $feedback): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $this->feedbackService->delete($feedback);

        return redirect()
            ->back()
            ->with('status', __('Review removed.'));
    }
}
