<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'product' => new ProductResource($this->when(
                $this->relationLoaded('productVariant') && $this->productVariant?->relationLoaded('product'),
                fn () => $this->productVariant->product,
            )),
            'quantity' => $this->quantity,
            'reorder_level' => $this->reorder_level,
            'reorder_quantity' => $this->reorder_quantity,
            'batch_number' => $this->batch_number,
            'expires_at' => $this->expires_at?->toDateString(),
            'notes' => $this->notes,
            'is_low_stock' => $this->isLowStock(),
            'adjustments' => InventoryAdjustmentResource::collection($this->whenLoaded('adjustments')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
