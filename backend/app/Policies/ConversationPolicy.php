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
     * Staff/admin may reply on any thread. Customers may only post on their own thread.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if ($user->isAdminOrStaff()) {
            return true;
        }

        return $user->isCustomer() && $conversation->user_id === $user->id;
    }
}
