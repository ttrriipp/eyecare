<?php

namespace App\Http\Controllers;

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

    public function destroy(Request $request, Feedback $feedback): RedirectResponse
    {
        $this->feedbackService->delete($feedback);

        return redirect()
            ->route('feedbacks.index', $request->query())
            ->with('status', __('Review deleted.'));
    }
}
