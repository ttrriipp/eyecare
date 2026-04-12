<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordBillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrStaff() ?? false;
    }

    public function rules(): array
    {
        /** @var Bill|null $bill */
        $bill = $this->route('bill');
        $maxBalance = $bill !== null
            ? number_format((float) $bill->balance_due, 2, '.', '')
            : '999999999999.99';

        return [
            'payment_method' => ['required', 'string', Rule::in(PaymentMethod::values())],
            'payment_amount' => ['required', 'numeric', 'gt:0', 'lte:'.$maxBalance],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => __('Record how payment was received (e.g. cash, GCash, bank transfer).'),
            'payment_amount.required' => __('Enter the amount received for this payment.'),
            'payment_amount.gt' => __('Payment amount must be greater than zero.'),
            'payment_amount.lte' => __('Payment amount cannot be greater than the remaining balance due.'),
        ];
    }
}
