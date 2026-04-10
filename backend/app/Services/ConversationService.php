<?php

namespace App\Services;

use App\Enums\ConversationStatus;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    /**
     * Return paginated conversations scoped by the caller's role.
     * Customers: own conversations only.
     * Staff/Admin: all conversations.
     * Ordered by last_message_at descending.
     */
    public function listForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Conversation::query()->with('user')->withCount('messages');

        if ($user->isCustomer()) {
            $query->forUser($user->id);
        }

        if (! empty($filters['status'])) {
            $status = ConversationStatus::tryFrom($filters['status']);
            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        $query->orderByRaw('last_message_at IS NULL ASC, last_message_at DESC');

        return $query->paginate($perPage);
    }

    /**
     * Create a new conversation for a customer.
     * Enforces the one-open-at-a-time rule: throws a ValidationException
     * if the customer already has an open conversation.
     */
    public function startConversation(User $user, array $data): Conversation
    {
        $existing = Conversation::forUser($user->id)->open()->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'conversation' => 'You already have an open conversation. Please continue there or wait for it to be closed before starting a new one.',
            ]);
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'subject' => $data['subject'] ?? null,
            'status'  => ConversationStatus::Open,
        ]);

        return $conversation->load('user');
    }

    /**
     * Return a single conversation after authorising access.
     * Throws 403 if a customer tries to access another user's conversation.
     */
    public function getConversation(User $user, Conversation $conversation): Conversation
    {
        if ($user->isCustomer() && $conversation->user_id !== $user->id) {
            abort(403, 'You do not have permission to view this conversation.');
        }

        return $conversation->load(['user', 'messages.sender']);
    }

    /**
     * Close a conversation (staff or admin).
     */
    public function closeConversation(Conversation $conversation): Conversation
    {
        $conversation->update(['status' => ConversationStatus::Closed]);

        return $conversation->fresh();
    }

    /**
     * Reopen a conversation (admin only).
     */
    public function reopenConversation(Conversation $conversation): Conversation
    {
        $conversation->update(['status' => ConversationStatus::Open]);

        return $conversation->fresh();
    }

    /**
     * Return the role-aware unread message count for the given user.
     *
     * Customer: messages in their own conversations not sent by them.
     * Staff/Admin: messages in open conversations sent by customers.
     */
    public function getUnreadCount(User $user): int
    {
        if ($user->isCustomer()) {
            return Message::query()
                ->whereHas('conversation', fn ($q) => $q->forUser($user->id))
                ->notSentBy($user->id)
                ->unread()
                ->count();
        }

        // Staff / Admin: unread messages from customers in all open conversations.
        return Message::query()
            ->whereHas('conversation', fn ($q) => $q->open())
            ->whereHas('sender', fn ($q) => $q->where('role', UserRole::Customer->value))
            ->unread()
            ->count();
    }
}
