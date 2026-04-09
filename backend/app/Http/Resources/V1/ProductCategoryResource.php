<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'name'                     => $this->name,
            'slug'                     => $this->slug,
            'description'              => $this->description,
            'has_ar_support'           => $this->has_ar_support,
            'requires_expiry_tracking' => $this->requires_expiry_tracking,
            'requires_prescription'    => $this->requires_prescription,
            'stock_unit'               => $this->stock_unit,
            'is_system'                => $this->is_system,
            'has_frame_size'           => $this->has_frame_size,
            'has_color'                => $this->has_color,
            'has_material'             => $this->has_material,
            'has_lens_type'            => $this->has_lens_type,
            'has_power_field'          => $this->has_power_field,
            'has_duration'             => $this->has_duration,
            'products_count'           => $this->whenCounted('products'),
            'created_at'               => $this->created_at->toISOString(),
            'updated_at'               => $this->updated_at->toISOString(),
        ];
    }
}
