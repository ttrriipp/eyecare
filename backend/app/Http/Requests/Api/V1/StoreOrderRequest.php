<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

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
            'items.*.product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where(function ($q) {
                    $q->where('is_active', true)
                        ->whereIn('product_id', Product::query()
                            ->where('is_active', true)
                            ->whereNull('deleted_at')
                            ->select('id'));
                }),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'appointment_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if (Schema::hasTable('appointments')) {
            $rules['appointment_id'][] = Rule::exists('appointments', 'id');
        }

        // Staff/admin can create orders on behalf of a customer or walk-in
        if ($this->user()->isAdminOrStaff()) {
            $rules['user_id'] = ['nullable', 'integer', 'exists:users,id'];
            $rules['walk_in_name'] = ['nullable', 'string', 'max:255'];
            $rules['walk_in_phone'] = ['nullable', 'string', 'max:20'];
            $rules['discount_amount'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_variant_id.exists' => 'The selected product variant does not exist or is not available.',
            'items.*.quantity.min' => 'Each item must have a quantity of at least 1.',
        ];
    }
}
