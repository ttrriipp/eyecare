<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;

class ConversationService
{
    /**
     * Return paginated conversations scoped by the caller's role.
     * Customers: own single thread only.
     * Staff/Admin: all conversations.
     * Ordered by last_message_at descending.
     */
    public function listForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        if ($user->isCustomer()) {
            return $this->listCanonicalCustomerConversation($user, $perPage);
        }

        $query = Conversation::query()->with('user')->withCount('messages');

        $query->orderByRaw('last_message_at IS NULL ASC, last_message_at DESC');

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Conversation>
     */
    private function listCanonicalCustomerConversation(User $user, int $perPage): ConcretePaginator
    {
        $canonical = Conversation::query()
            ->with('user')
            ->withCount('messages')
            ->forUser($user->id)
            ->orderBy('id')
            ->first();

        if ($canonical === null) {
            return new ConcretePaginator(collect(), 0, $perPage, 1);
        }

        return new ConcretePaginator(collect([$canonical]), 1, $perPage, 1);
    }

    /**
     * Return the single persistent thread for a customer, creating the row only when this runs.
     */
    public function getOrCreateForCustomer(User $user, array $data = []): Conversation
    {
        $existing = Conversation::forUser($user->id)->orderBy('id')->first();

        if ($existing !== null) {
            return $existing->load('user');
        }

        $conversation = Conversation::create([
            'user_id' => $user->id,
        ]);

        return $conversation->load('user');
    }

    /**
     * Idempotent: returns the customer's single thread or creates it.
     */
    public function startConversation(User $user, array $data): Conversation
    {
        return $this->getOrCreateForCustomer($user, $data);
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
     * Return the role-aware unread message count for the given user.
     *
     * Customer: messages in their own conversation not sent by them.
     * Staff/Admin: unread messages from customers in any conversation.
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

        return Message::query()
            ->whereHas('sender', fn ($q) => $q->where('role', UserRole::Customer->value))
            ->unread()
            ->count();
    }
}
