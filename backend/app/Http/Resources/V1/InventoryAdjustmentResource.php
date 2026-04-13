<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inventory_id' => $this->inventory_id,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'delta' => $this->delta,
            'adjustment_type' => $this->adjustment_type,
            'reason' => $this->reason,
            'adjusted_by' => new UserResource($this->whenLoaded('adjustedBy')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
