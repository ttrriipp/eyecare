<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'appointment_type' => $this->appointment_type,
            'doctor_name' => $this->doctor_name,
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
