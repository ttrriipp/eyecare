<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\RecordBillPaymentRequest;
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

        if ($user->isStaff()) {
            $bill->load(['paymentHistories.actor']);
        }

        return view('billing.show', [
            'bill' => $bill,
        ]);
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
     * Admin only: refund paid bill (plan).
     */
    public function refund(Request $request, Bill $bill): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $bill = $this->billingService->refund($bill, $request->user());

        return redirect()
            ->route('orders.billing.show', $bill)
            ->with('status', __('Invoice :invoice marked as refunded.', ['invoice' => $bill->invoice_number]));
    }
}
