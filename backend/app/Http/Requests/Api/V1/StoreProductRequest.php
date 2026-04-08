<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:unisex,men,women,kids'],
            'ar_model_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
