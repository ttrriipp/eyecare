<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'color' => $this->color,
            'frame_size' => $this->frame_size,
            'material' => $this->material,
            'lens_type' => $this->lens_type,
            'base_curve' => $this->base_curve,
            'diameter' => $this->diameter,
            'price_adjustment' => $this->price_adjustment,
            'is_default' => $this->is_default,
            'unit_price' => $this->when($this->relationLoaded('product'), fn () => (float) $this->unitPrice()),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
