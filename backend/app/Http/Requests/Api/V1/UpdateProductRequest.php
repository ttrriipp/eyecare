<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:product_categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'sku' => ['sometimes', 'string', 'max:100', Rule::unique('products')->ignore($this->route('product'))],
            'brand' => ['nullable', 'string', 'max:255'],
            'lens_type' => ['nullable', 'string', 'max:255'],
            'frame_material' => ['nullable', 'string', 'max:255'],
            'ar_model_url' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
