<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\RecordBillPaymentRequest;
use App\Http\Requests\RefundBillRequest;
use App\Http\Requests\UpdateBillOfficialReceiptRequest;
use App\Models\Bill;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * Invoice list: staff/admin see all; customers see bills for their own orders (plan: manual payment recording, no gateway).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $filters = $request->only(['payment_status', 'search', 'date_from', 'date_to', 'sort_by', 'sort_dir']);

        if ($user->isCustomer()) {
            $filters['user_id'] = $user->id;
        }

        $bills = $this->billingService->list(
            filters: $filters,
            perPage: 15,
        );

        return view('billing.index', [
            'bills' => $bills,
            'filters' => $filters,
            'statuses' => PaymentStatus::cases(),
        ]);
    }

    public function show(Request $request, Bill $bill): View
    {
        $user = $request->user();
        $bill = $this->billingService->find($bill->id);

        if ($user->isCustomer()) {
            $bill->loadMissing('order');
            if (! $bill->order || (int) $bill->order->user_id !== (int) $user->id) {
                abort(403);
            }
        }

        if ($user->isAdminOrStaff()) {
            $bill->load(['paymentHistories.actor']);
        }

        return view('billing.show', [
            'bill' => $bill,
        ]);
    }

    /**
     * Printable invoice / receipt (same access as show).
     */
    public function print(Request $request, Bill $bill): View
    {
        $user = $request->user();
        $bill = $this->billingService->find($bill->id);

        if ($user->isCustomer()) {
            $bill->loadMissing('order');
            if (! $bill->order || (int) $bill->order->user_id !== (int) $user->id) {
                abort(403);
            }
        }

        return view('billing.print', [
            'bill' => $bill,
        ]);
    }

    /**
     * Staff/admin: set BIR official receipt number (separate from internal invoice #).
     */
    public function updateOfficialReceipt(UpdateBillOfficialReceiptRequest $request, Bill $bill): RedirectResponse
    {
        $bill = $this->billingService->updateOfficialReceiptNumber(
            $bill,
            $request->validated('official_receipt_number'),
        );

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', __('Official receipt number saved for :invoice.', ['invoice' => $bill->invoice_number]));
    }

    /**
     * Staff/admin: mark payment received (plan — no payment gateway).
     */
    public function pay(RecordBillPaymentRequest $request, Bill $bill): RedirectResponse
    {
        $validated = $request->validated();
        $bill = $this->billingService->recordPayment(
            $bill,
            (float) $validated['payment_amount'],
            $validated['payment_method'],
            $request->user()->id,
        );

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', $bill->isPaid()
                ? __('Invoice :invoice is now fully paid.', ['invoice' => $bill->invoice_number])
                : __('Partial payment recorded for :invoice.', ['invoice' => $bill->invoice_number]));
    }

    /**
     * Admin only: void unpaid bill (plan).
     */
    public function void(Request $request, Bill $bill): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $bill = $this->billingService->void($bill, $request->user());

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', __('Invoice :invoice marked as voided.', ['invoice' => $bill->invoice_number]));
    }

    /**
     * Staff/admin: record a refund (partial or full) with audit trail.
     */
    public function refund(RefundBillRequest $request, Bill $bill): RedirectResponse
    {
        $validated = $request->validated();

        $bill = $this->billingService->refund(
            $bill,
            $request->user(),
            (float) $validated['refund_amount'],
            PaymentMethod::from($validated['refund_method']),
            $validated['note'] ?? null,
        );

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', $bill->isPartiallyRefunded()
                ? __('Partial refund recorded for :invoice.', ['invoice' => $bill->invoice_number])
                : __('Invoice :invoice fully refunded.', ['invoice' => $bill->invoice_number]));
    }
}
