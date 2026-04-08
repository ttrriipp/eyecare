<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_verified_purchase' => $this->is_verified_purchase,
            'is_visible' => $this->is_visible,
            'admin_reply' => $this->admin_reply,
            'moderated_by' => $this->moderated_by,
            'moderated_at' => $this->moderated_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
