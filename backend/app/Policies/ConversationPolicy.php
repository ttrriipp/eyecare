<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Customers may only view their own conversations.
     * Staff and admin may view all.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isAdminOrStaff()) {
            return true;
        }

        return $user->isCustomer() && $conversation->user_id === $user->id;
    }

    /**
     * Same ownership rule as view, plus the conversation must be open.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (! $conversation->isOpen()) {
            return false;
        }

        if ($user->isAdminOrStaff()) {
            return true;
        }

        return $user->isCustomer() && $conversation->user_id === $user->id;
    }

    /**
     * Staff and admin may close conversations.
     */
    public function close(User $user, Conversation $conversation): bool
    {
        return $user->isAdminOrStaff();
    }

    /**
     * Only admin may reopen a conversation.
     */
    public function reopen(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }
}
