<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MessageService
{
    /**
     * Return all messages for a conversation (oldest first) and
     * mark as read any unread messages not sent by the requesting user.
     */
    public function listMessages(User $user, Conversation $conversation): Collection
    {
        // Mark unread messages not authored by this user as read.
        Message::query()
            ->where('conversation_id', $conversation->id)
            ->notSentBy($user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $conversation
            ->messages()
            ->with('sender')
            ->oldest()
            ->get();
    }

    /**
     * Send a new message in a conversation and bump last_message_at.
     */
    public function sendMessage(User $user, Conversation $conversation, array $data): Message
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $data['body'],
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message->load('sender');
    }
}
