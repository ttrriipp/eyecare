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
            'name'                     => ['required', 'string', 'max:255'],
            'slug'                     => ['required', 'string', 'max:255', 'unique:product_categories,slug'],
            'description'              => ['nullable', 'string'],
            'has_ar_support'           => ['sometimes', 'boolean'],
            'requires_expiry_tracking' => ['sometimes', 'boolean'],
            'requires_prescription'    => ['nullable', 'boolean'],
            'stock_unit'               => ['required', 'string', 'in:units,pairs,boxes'],
            'has_frame_size'           => ['nullable', 'boolean'],
            'has_color'                => ['nullable', 'boolean'],
            'has_material'             => ['nullable', 'boolean'],
            'has_lens_type'            => ['nullable', 'boolean'],
            'has_power_field'          => ['nullable', 'boolean'],
            'has_duration'             => ['nullable', 'boolean'],
            // is_system is never accepted from API consumers
        ];
    }

    /**
     * Force is_system to false on create — API consumers cannot set system categories.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key === null) {
            $data['is_system'] = false;
        }

        return $data;
    }
}
