<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender'          => $this->whenLoaded('sender', fn () => [
                'id'   => $this->sender->id,
                'name' => $this->sender->name,
                'role' => $this->sender->role->value,
            ]),
            'body'            => $this->body,
            'is_read'         => $this->is_read,
            'read_at'         => $this->read_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
