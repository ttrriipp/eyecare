<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'sku' => $this->when($this->relationLoaded('productVariant'), fn () => $this->productVariant?->sku),
            'product' => new ProductResource($this->when(
                $this->relationLoaded('productVariant') && $this->productVariant?->relationLoaded('product'),
                fn () => $this->productVariant->product,
            )),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'subtotal' => $this->subtotal,
        ];
    }
}
