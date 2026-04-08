<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdminOrStaff() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('customer_type') === 'walk_in') {
            $this->merge(['user_id' => null]);
        }

        if ($this->input('customer_type') === 'registered') {
            $this->merge([
                'walk_in_name' => null,
                'walk_in_phone' => null,
            ]);
        }

        $items = $this->input('items', []);
        $filtered = [];
        foreach ($items as $row) {
            $variantId = $row['product_variant_id'] ?? null;
            $quantity = isset($row['quantity']) ? (int) $row['quantity'] : 0;
            if ($variantId !== null && $variantId !== '' && $quantity >= 1) {
                $filtered[] = [
                    'product_variant_id' => (int) $variantId,
                    'quantity' => $quantity,
                ];
            }
        }
        $this->merge(['items' => $filtered]);
    }

    public function rules(): array
    {
        return [
            'customer_type' => ['required', 'in:registered,walk_in'],
            'user_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('customer_type') === 'registered'),
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', UserRole::Customer->value)
                        ->whereNull('deleted_at');
                }),
            ],
            'walk_in_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->input('customer_type') === 'walk_in'),
            ],
            'walk_in_phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::requiredIf(fn () => $this->input('customer_type') === 'walk_in'),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where(function ($q) {
                    $q->whereIn('product_id', Product::query()
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->select('id'));
                }),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required_if' => __('Select a registered customer.'),
            'walk_in_name.required_if' => __('Enter the walk-in customer name.'),
            'walk_in_phone.required_if' => __('Enter the walk-in phone number.'),
            'items.required' => __('Add at least one product line.'),
            'items.min' => __('Add at least one product line.'),
        ];
    }
}
