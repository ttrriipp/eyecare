<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Order;
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
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    /**
     * Mark a bill as paid.
     */
    public function markAsPaid(Bill $bill, string $paymentMethod): Bill
    {
        if (! $bill->payment_status->canTransitionTo(PaymentStatus::Paid)) {
            throw ValidationException::withMessages([
                'payment_status' => "Cannot mark a '{$bill->payment_status->label()}' bill as paid.",
            ]);
        }

        $bill->update([
            'payment_status' => PaymentStatus::Paid,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
        ]);

        return $bill->fresh(['order.user']);
    }

    /**
     * Void an unpaid bill (admin only).
     */
    public function void(Bill $bill): Bill
    {
        if (! $bill->payment_status->canTransitionTo(PaymentStatus::Voided)) {
            throw ValidationException::withMessages([
                'payment_status' => "Cannot void a '{$bill->payment_status->label()}' bill.",
            ]);
        }

        $bill->update(['payment_status' => PaymentStatus::Voided]);

        return $bill->fresh(['order.user']);
    }

    /**
     * Refund a paid bill (admin only).
     */
    public function refund(Bill $bill): Bill
    {
        if (! $bill->payment_status->canTransitionTo(PaymentStatus::Refunded)) {
            throw ValidationException::withMessages([
                'payment_status' => "Cannot refund a '{$bill->payment_status->label()}' bill.",
            ]);
        }

        $bill->update(['payment_status' => PaymentStatus::Refunded]);

        return $bill->fresh(['order.user']);
    }

    /**
     * Handle bill when an order is cancelled.
     * Unpaid → Voided, Paid → Refunded.
     */
    public function handleOrderCancellation(Order $order): void
    {
        $bill = $order->bill;

        if (! $bill) {
            return;
        }

        if ($bill->isUnpaid()) {
            $bill->update(['payment_status' => PaymentStatus::Voided]);
        } elseif ($bill->isPaid()) {
            $bill->update(['payment_status' => PaymentStatus::Refunded]);
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
}
