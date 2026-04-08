<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordBillPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:100'],
            'payment_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => __('Record how payment was received (e.g. cash, GCash, bank transfer).'),
            'payment_amount.required' => __('Enter the amount received for this payment.'),
            'payment_amount.gt' => __('Payment amount must be greater than zero.'),
        ];
    }
}
