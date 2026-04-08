<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('product_categories')->ignore($this->route('category'))],
            'description' => ['nullable', 'string'],
            'has_ar_support' => ['sometimes', 'boolean'],
            'requires_expiry_tracking' => ['sometimes', 'boolean'],
        ];
    }
}
