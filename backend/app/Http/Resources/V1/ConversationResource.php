<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUser = $request->user();

        // Unread count for the authenticated user in this conversation.
        $unreadCount = $this->messages
            ->filter(fn ($m) => ! $m->is_read && $m->sender_id !== $authUser?->id)
            ->count();

        return [
            'id' => $this->id,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'message_count' => $this->whenCounted('messages', fn () => $this->messages_count),
            'unread_count' => $this->when(
                $this->relationLoaded('messages'),
                $unreadCount
            ),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
