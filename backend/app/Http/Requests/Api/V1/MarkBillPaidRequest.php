<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PaymentMethod;
use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkBillPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminOrStaff();
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
            'payment_method.required' => 'A payment method is required (e.g., cash, GCash, bank transfer).',
            'payment_amount.required' => 'A payment amount is required.',
            'payment_amount.gt' => 'Payment amount must be greater than zero.',
            'payment_amount.lte' => 'Payment amount cannot be greater than the remaining balance due.',
        ];
    }
}
