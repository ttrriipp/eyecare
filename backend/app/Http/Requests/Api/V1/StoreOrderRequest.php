<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // All authenticated users (admin, staff, customer) can create orders
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // Staff/admin can create orders on behalf of a customer or walk-in
        if ($this->user()->isAdminOrStaff()) {
            $rules['user_id'] = ['nullable', 'integer', 'exists:users,id'];
            $rules['walk_in_name'] = ['nullable', 'string', 'max:255'];
            $rules['walk_in_phone'] = ['nullable', 'string', 'max:20'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.exists' => 'The selected product does not exist.',
            'items.*.quantity.min' => 'Each item must have a quantity of at least 1.',
        ];
    }
}
