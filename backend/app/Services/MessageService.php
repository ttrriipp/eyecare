<?php

namespace App\Services;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

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
     * Send a new message in a conversation.
     * Validates the conversation is still open, creates the message,
     * and bumps last_message_at on the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation, array $data): Message
    {
        if (! $conversation->isOpen()) {
            throw ValidationException::withMessages([
                'conversation' => 'Cannot send a message to a closed conversation.',
            ]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $data['body'],
            'is_read'         => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message->load('sender');
    }
}
