<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationService;
use App\Services\MessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MessagingThread extends Component
{
    #[Locked]
    public int $conversationId;

    public string $body = '';

    // ── Polling (scheduled from Blade via wire:poll — see messaging-thread.blade.php) ───

    /** Refetches messages for the open thread (new bubbles without a manual reload). */
    public function pollMessages(): void
    {
        unset($this->threadMessages);
    }

    // ── Computed ───────────────────────────────────────────────────────────

    #[Computed]
    public function conversation(): Conversation
    {
        return app(ConversationService::class)->getConversation(
            user: auth()->user(),
            conversation: Conversation::findOrFail($this->conversationId),
        );
    }

    /** @return Collection<int, Message> */
    #[Computed]
    public function threadMessages(): Collection
    {
        return app(MessageService::class)->listMessages(
            user: auth()->user(),
            conversation: Conversation::findOrFail($this->conversationId),
        );
    }

    // ── Actions ────────────────────────────────────────────────────────────

    public function sendMessage(): void
    {
        $this->validate(['body' => 'required|string|min:1|max:5000']);

        app(MessageService::class)->sendMessage(
            user: auth()->user(),
            conversation: Conversation::findOrFail($this->conversationId),
            data: ['body' => $this->body],
        );

        $this->body = '';
        unset($this->threadMessages);
        $this->dispatch('conversation-updated');
        $this->dispatch('scroll-to-bottom');
    }

    // ── Render ─────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.messaging.messaging-thread');
    }
}
