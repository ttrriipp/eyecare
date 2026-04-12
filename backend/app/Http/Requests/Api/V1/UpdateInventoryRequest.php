<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\InventoryAdjustmentReason;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
            'reorder_quantity' => ['sometimes', 'integer', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expires_at' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $this->categoryRequiresExpiryTracking()),
            ],
            'adjustment_reason' => [
                Rule::requiredIf(fn () => $this->filled('quantity')),
                'nullable',
                'string',
                Rule::enum(InventoryAdjustmentReason::class),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function categoryRequiresExpiryTracking(): bool
    {
        $product = $this->route('product');

        if (! $product instanceof Product) {
            return false;
        }

        return (bool) optional($product->category)->requires_expiry_tracking;
    }
}
