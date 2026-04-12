<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Messaging;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Poll;
use Livewire\Component;
use Livewire\WithPagination;

class MessagingInbox extends Component
{
    use WithPagination;

    public ?int $selectedConversationId = null;

    /** Track last-known unread count for silent-refresh logic. */
    public int $lastUnreadCount = -1;

    public function mount(): void
    {
        $this->lastUnreadCount = app(ConversationService::class)
            ->getUnreadCount(auth()->user());
    }

    // ── Polling ───────────────────────────────────────────────────────────────

    #[Poll(10000)]
    public function pollUnreadCount(): void
    {
        $count = app(ConversationService::class)->getUnreadCount(auth()->user());

        if ($count !== $this->lastUnreadCount) {
            $this->lastUnreadCount = $count;
            // Silently refresh the conversation list without flicker.
            unset($this->conversations);
        }
    }

    // ── Computed ─────────────────────────────────────────────────────────────

    /** @return LengthAwarePaginator<int, Conversation> */
    #[Computed]
    public function conversations(): LengthAwarePaginator
    {
        return app(ConversationService::class)->listForUser(
            user: auth()->user(),
            filters: [],
            perPage: 20,
        );
    }

    // ── Row selection ─────────────────────────────────────────────────────────

    public function selectConversation(int $id): void
    {
        $this->selectedConversationId = ($this->selectedConversationId === $id) ? null : $id;
    }

    public function closeThread(): void
    {
        $this->selectedConversationId = null;
    }

    // ── Event listeners (from MessagingThread child) ──────────────────────────

    #[On('conversation-updated')]
    public function onConversationUpdated(): void
    {
        unset($this->conversations);
    }

    // ── Helpers (called from blade) ───────────────────────────────────────────

    public function isCustomerRole(): bool
    {
        return auth()->user()?->role === UserRole::Customer;
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.messaging.messaging-inbox')
            ->layout('layouts.app', ['title' => __('Messages')]);
    }
}
