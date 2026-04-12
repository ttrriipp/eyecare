<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffStoreBillRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        /** @var Bill|null $bill */
        $bill = $this->route('bill');
        $maxPaid = $bill !== null
            ? number_format((float) $bill->amount_paid, 2, '.', '')
            : '999999999999.99';

        return [
            'refund_amount' => ['required', 'numeric', 'gt:0', 'lte:'.$maxPaid],
            'refund_method' => ['required', 'string', Rule::in(PaymentMethod::values())],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'refund_amount.required' => __('Enter the refund amount.'),
            'refund_amount.lte' => __('Refund amount cannot exceed amount paid.'),
            'refund_method.required' => __('Select how the refund would be issued.'),
        ];
    }
}
