<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:product_categories,slug'],
            'description' => ['nullable', 'string'],
            'has_ar_support' => ['sometimes', 'boolean'],
            'requires_expiry_tracking' => ['sometimes', 'boolean'],
        ];
    }
}
