<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\FrameMaterial;
use App\Enums\LensType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'brand' => ['nullable', 'string', 'max:255'],
            'lens_type' => ['nullable', 'string', Rule::in(LensType::values())],
            'frame_material' => ['nullable', 'string', Rule::in(FrameMaterial::values())],
            'ar_model_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
