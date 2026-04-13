<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'appointment_id' => $this->appointment_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'appointment' => $this->whenLoaded('appointment', function () {
                return [
                    'id' => $this->appointment?->id,
                    'status' => data_get($this->appointment, 'status'),
                    'scheduled_at' => data_get($this->appointment, 'scheduled_at'),
                ];
            }),
            'walk_in_name' => $this->walk_in_name,
            'walk_in_phone' => $this->walk_in_phone,
            'is_walk_in' => $this->isWalkIn(),
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
