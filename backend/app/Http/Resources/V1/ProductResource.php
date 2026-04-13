<?php

namespace App\Http\Resources\V1;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $displayVariant = $this->resolveDefaultVariantForApi($request);
        // Catalog clients expect `images` in list/detail payloads.
        // If `images` wasn't eager-loaded, fall back to shared gallery rows,
        // then to default-variant images to keep older mobile UI working.
        $gallery = $this->whenLoaded('images');
        if ($gallery === null) {
            if ($this->relationLoaded('sharedImages')) {
                $gallery = $this->sharedImages;
            } elseif ($this->relationLoaded('defaultVariant') && $this->defaultVariant?->relationLoaded('images')) {
                $gallery = $this->defaultVariant->images;
            } else {
                $gallery = collect();
            }
        }

        return [
            'id' => $this->id,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) ($displayVariant?->price ?? 0),
            'sku' => $this->when($request->user()?->isAdminOrStaff(), $this->sku),
            'brand' => $this->brand,
            'is_active' => $this->when($request->user()?->isAdmin(), $this->is_active),
            'images' => ProductImageResource::collection($gallery),
            'default_variant' => $this->when(
                $displayVariant !== null,
                fn () => new ProductVariantResource($displayVariant),
            ),
            'variants' => $this->when(
                $this->relationLoaded('variants'),
                function () use ($request) {
                    $list = $request->user()?->isAdminOrStaff()
                        ? $this->variants
                        : $this->variants->where('is_active', true)->values();

                    return ProductVariantResource::collection($list);
                },
            ),
            'average_rating' => $this->average_rating !== null ? round((float) $this->average_rating, 1) : null,
            'reviews_count' => $this->reviews_count ?? 0,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Staff/admin see the true default variant; customers see the default only when it is active,
     * otherwise the first active SKU by id (for list price and default_variant payload).
     */
    protected function resolveDefaultVariantForApi(Request $request): ?ProductVariant
    {
        $default = $this->relationLoaded('defaultVariant') ? $this->defaultVariant : null;

        if ($request->user()?->isAdminOrStaff()) {
            return $default;
        }

        if ($default && $default->is_active) {
            return $default;
        }

        if ($this->relationLoaded('variants')) {
            return $this->variants->where('is_active', true)->sortBy('id')->first();
        }

        return null;
    }
}
