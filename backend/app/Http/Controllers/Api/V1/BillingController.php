<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MarkBillPaidRequest;
use App\Http\Requests\Api\V1\RefundBillRequest;
use App\Http\Requests\UpdateBillOfficialReceiptRequest;
use App\Http\Resources\V1\BillResource;
use App\Models\Bill;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * List bills.
     * Staff/admin see all; customers see only their own.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['payment_status', 'search', 'date_from', 'date_to', 'sort_by', 'sort_dir']);

        // Customers can only see bills for their own orders
        if ($request->user()->isCustomer()) {
            $filters['user_id'] = $request->user()->id;
        }

        $bills = $this->billingService->list(
            filters: $filters,
            perPage: $request->integer('per_page', 15),
        );

        return BillResource::collection($bills);
    }

    /**
     * View a single bill.
     */
    public function show(Request $request, Bill $bill): BillResource
    {
        // Customers can only view bills for their own orders
        if ($request->user()->isCustomer()) {
            $bill->load('order');
            if ($bill->order && $bill->order->user_id !== $request->user()->id) {
                abort(403, 'You do not have permission to view this bill.');
            }
        }

        $bill = $this->billingService->find($bill->id);

        return new BillResource($bill);
    }

    /**
     * Record bill payment (partial or full; admin/staff only).
     */
    public function markAsPaid(MarkBillPaidRequest $request, Bill $bill): JsonResponse
    {
        $validated = $request->validated();
        $bill = $this->billingService->recordPayment(
            $bill,
            (float) $validated['payment_amount'],
            $validated['payment_method'],
            $request->user()->id,
        );

        return response()->json([
            'message' => $bill->isPaid() ? 'Bill fully paid.' : 'Partial payment recorded.',
            'bill' => new BillResource($bill),
        ]);
    }

    /**
     * Set BIR official receipt number (staff/admin).
     */
    public function updateOfficialReceipt(UpdateBillOfficialReceiptRequest $request, Bill $bill): JsonResponse
    {
        $bill = $this->billingService->updateOfficialReceiptNumber(
            $bill,
            $request->validated('official_receipt_number'),
        );

        return response()->json([
            'message' => 'Official receipt number saved.',
            'bill' => new BillResource($bill),
        ]);
    }

    /**
     * Void an unpaid bill (admin only).
     */
    public function void(Request $request, Bill $bill): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403, 'Only administrators can void bills.');
        }

        $bill = $this->billingService->void($bill, $request->user());

        return response()->json([
            'message' => 'Bill voided.',
            'bill' => new BillResource($bill),
        ]);
    }

    /**
     * Record a refund (admin only).
     */
    public function refund(RefundBillRequest $request, Bill $bill): JsonResponse
    {
        $validated = $request->validated();

        $bill = $this->billingService->refund(
            $bill,
            $request->user(),
            (float) $validated['refund_amount'],
            PaymentMethod::from($validated['refund_method']),
            (int) $request->user()->id,
            $validated['note'] ?? null,
        );

        return response()->json([
            'message' => $bill->isPartiallyRefunded() ? 'Partial refund recorded.' : 'Bill fully refunded.',
            'bill' => new BillResource($bill),
        ]);
    }
}
