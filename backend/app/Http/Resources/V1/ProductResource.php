<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'cost_per_unit' => $this->when($request->user()?->isAdminOrStaff(), $this->cost_per_unit),
            'sku' => $this->when($request->user()?->isAdminOrStaff(), $this->sku),
            'brand' => $this->brand,
            'gender' => $this->gender,
            'ar_model_url' => $this->ar_model_url,
            'is_active' => $this->when($request->user()?->isAdmin(), $this->is_active),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'default_variant' => new ProductVariantResource($this->whenLoaded('defaultVariant')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'average_rating' => $this->average_rating !== null ? round((float) $this->average_rating, 1) : null,
            'reviews_count' => $this->reviews_count ?? 0,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
