<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MarkBillPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdminOrStaff();
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'A payment method is required (e.g., cash, GCash, bank transfer).',
        ];
    }
}
