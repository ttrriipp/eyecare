<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\BillingPaymentHistory;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class BillingService
{
    /**
     * List bills with filters and pagination.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Bill::query()
            ->with(['order.user']);

        if (! empty($filters['payment_status'])) {
            $query->byPaymentStatus(PaymentStatus::from($filters['payment_status']));
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['user_id'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Admin audit report: all billing payment activity (paginated).
     *
     * @return LengthAwarePaginator<int, BillingPaymentHistory>
     */
    public function listPaymentHistory(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = BillingPaymentHistory::query()
            ->with(['bill.order', 'actor'])
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->whereHas('bill', function ($bq) use ($term) {
                    $bq->where('invoice_number', 'like', "%{$term}%")
                        ->orWhereHas('order', function ($oq) use ($term) {
                            $oq->where('order_number', 'like', "%{$term}%");
                        });
                })->orWhereHas('actor', function ($aq) use ($term) {
                    $aq->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            });
        }

        if (! empty($filters['actor_user_id'])) {
            $query->where('actor_user_id', (int) $filters['actor_user_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find a bill by ID with order loaded.
     */
    public function find(int $billId): Bill
    {
        return Bill::with(['order.user', 'order.items.productVariant.product'])->findOrFail($billId);
    }

    /**
     * Create a bill for an order.
     */
    public function createForOrder(Order $order): Bill
    {
        return Bill::create([
            'order_id' => $order->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $order->total_amount,
            'amount_paid' => 0,
            'balance_due' => $order->total_amount,
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    /**
     * Record bill payment (partial or full).
     */
    public function recordPayment(
        Bill $bill,
        float $paymentAmount,
        string $paymentMethod,
        ?int $collectedBy = null
    ): Bill {
        if (in_array($bill->payment_status, [PaymentStatus::Voided, PaymentStatus::Refunded], true)) {
            throw ValidationException::withMessages([
                'payment_status' => "Cannot record payment for a '{$bill->payment_status->label()}' bill.",
            ]);
        }

        if ($paymentAmount <= 0) {
            throw ValidationException::withMessages([
                'payment_amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $remaining = (float) $bill->balance_due;
        if ($paymentAmount > $remaining) {
            throw ValidationException::withMessages([
                'payment_amount' => 'Payment amount cannot be greater than the remaining balance.',
            ]);
        }

        $newAmountPaid = round(((float) $bill->amount_paid) + $paymentAmount, 2);
        $newBalanceDue = round(((float) $bill->amount) - $newAmountPaid, 2);
        $isFullyPaid = $newBalanceDue <= 0;
        $normalizedMethod = PaymentMethod::from(strtolower(trim($paymentMethod)));

        $fromStatus = $bill->payment_status;

        $bill->update([
            'payment_status' => $isFullyPaid ? PaymentStatus::Paid : PaymentStatus::PartiallyPaid,
            'amount_paid' => $newAmountPaid,
            'balance_due' => max($newBalanceDue, 0),
            'payment_method' => $normalizedMethod,
            'collected_by' => $collectedBy,
            'paid_at' => $isFullyPaid ? now() : null,
        ]);

        $bill = $bill->fresh(['order.user', 'collector']);

        if ($collectedBy !== null) {
            $actor = User::query()->find($collectedBy);
            $this->recordBillingActivity(
                $bill,
                $actor,
                BillingPaymentHistory::ACTION_PAYMENT_RECORDED,
                $paymentAmount,
                $normalizedMethod,
                $fromStatus,
                $bill->payment_status,
                null,
            );
        }

        return $bill;
    }

    /**
     * Void an unpaid bill (admin only).
     */
    public function void(Bill $bill, User $actor): Bill
    {
        if (! $bill->payment_status->canTransitionTo(PaymentStatus::Voided)) {
            throw ValidationException::withMessages([
                'payment_status' => "Cannot void a '{$bill->payment_status->label()}' bill.",
            ]);
        }

        $fromStatus = $bill->payment_status;

        $bill->update(['payment_status' => PaymentStatus::Voided]);

        $bill = $bill->fresh(['order.user']);

        $this->recordBillingActivity(
            $bill,
            $actor,
            BillingPaymentHistory::ACTION_VOIDED,
            null,
            null,
            $fromStatus,
            PaymentStatus::Voided,
            null,
        );

        return $bill;
    }

    /**
     * Refund a paid bill (admin only).
     */
    public function refund(Bill $bill, User $actor): Bill
    {
        if (! $bill->payment_status->canTransitionTo(PaymentStatus::Refunded)) {
            throw ValidationException::withMessages([
                'payment_status' => "Cannot refund a '{$bill->payment_status->label()}' bill.",
            ]);
        }

        $fromStatus = $bill->payment_status;

        $bill->update(['payment_status' => PaymentStatus::Refunded]);

        $bill = $bill->fresh(['order.user']);

        $this->recordBillingActivity(
            $bill,
            $actor,
            BillingPaymentHistory::ACTION_REFUNDED,
            null,
            null,
            $fromStatus,
            PaymentStatus::Refunded,
            null,
        );

        return $bill;
    }

    /**
     * Handle bill when an order is cancelled.
     * Unpaid → Voided, Partial/Paid → Refunded.
     */
    public function handleOrderCancellation(Order $order, ?User $actor = null): void
    {
        $bill = $order->bill;

        if (! $bill) {
            return;
        }

        $fromStatus = $bill->payment_status;

        if ($bill->isUnpaid()) {
            $bill->update(['payment_status' => PaymentStatus::Voided]);
        } elseif ($bill->isPartiallyPaid() || $bill->isPaid()) {
            $bill->update(['payment_status' => PaymentStatus::Refunded]);
        } else {
            return;
        }

        $bill = $bill->fresh();

        if ($bill->payment_status !== $fromStatus) {
            $this->recordBillingActivity(
                $bill,
                $actor,
                BillingPaymentHistory::ACTION_UPDATED_FROM_ORDER_CANCELLATION,
                null,
                null,
                $fromStatus,
                $bill->payment_status,
                __('Bill updated because the order was cancelled.'),
            );
        }
    }

    /**
     * Generate a unique invoice number in the format INV-YYYYMMDD-XXXXX.
     */
    private function generateInvoiceNumber(): string
    {
        $datePrefix = 'INV-'.now()->format('Ymd').'-';

        $lastBill = Bill::where('invoice_number', 'like', $datePrefix.'%')
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastBill) {
            $lastSequence = (int) substr($lastBill->invoice_number, -5);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $datePrefix.str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
    }

    private function recordBillingActivity(
        Bill $bill,
        ?User $actor,
        string $action,
        ?float $amount,
        ?PaymentMethod $paymentMethod,
        PaymentStatus $fromStatus,
        PaymentStatus $toStatus,
        ?string $note,
    ): void {
        BillingPaymentHistory::query()->create([
            'bill_id' => $bill->id,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'amount' => $amount !== null ? round($amount, 2) : null,
            'payment_method' => $paymentMethod?->value,
            'from_payment_status' => $fromStatus->value,
            'to_payment_status' => $toStatus->value,
            'note' => $note,
        ]);
    }
}
