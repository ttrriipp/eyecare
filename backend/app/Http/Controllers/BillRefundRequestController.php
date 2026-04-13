<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\RefundRequestStatus;
use App\Http\Requests\RejectBillRefundRequestRequest;
use App\Http\Requests\StaffStoreBillRefundRequest;
use App\Models\Bill;
use App\Models\BillRefundRequest;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillRefundRequestController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * Admin: queue of pending staff refund requests.
     */
    public function index(Request $request): View
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $requests = BillRefundRequest::query()
            ->with(['bill.order', 'requestedBy'])
            ->where('status', RefundRequestStatus::Pending)
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('billing.refund-requests.index', [
            'requests' => $requests,
        ]);
    }

    public function store(StaffStoreBillRefundRequest $request, Bill $bill): RedirectResponse
    {
        $user = $request->user();
        $bill = $this->billingService->find($bill->id);

        $validated = $request->validated();

        $this->billingService->createRefundRequest(
            $bill,
            $user,
            (float) $validated['refund_amount'],
            PaymentMethod::from($validated['refund_method']),
            $validated['note'] ?? null,
        );

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', __('Refund request submitted. An administrator will review it.'));
    }

    public function approve(Request $request, Bill $bill, BillRefundRequest $billRefundRequest): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        if ((int) $billRefundRequest->bill_id !== (int) $bill->id) {
            abort(404);
        }

        $bill = $this->billingService->approveRefundRequest($billRefundRequest, $request->user());

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', $bill->isPartiallyRefunded()
                ? __('Partial refund recorded from approved request for :invoice.', ['invoice' => $bill->invoice_number])
                : __('Invoice :invoice fully refunded (approved request).', ['invoice' => $bill->invoice_number]));
    }

    public function reject(RejectBillRefundRequestRequest $request, Bill $bill, BillRefundRequest $billRefundRequest): RedirectResponse
    {
        if ((int) $billRefundRequest->bill_id !== (int) $bill->id) {
            abort(404);
        }

        $this->billingService->rejectRefundRequest(
            $billRefundRequest,
            $request->user(),
            $request->validated('rejection_reason'),
        );

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', __('Refund request rejected.'));
    }
}
