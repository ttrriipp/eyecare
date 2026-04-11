<div
    class="flex min-h-0 w-full max-h-[calc(100dvh-8rem)] flex-1 flex-col gap-4 overflow-hidden sm:max-h-[calc(100dvh-7rem)] lg:max-h-[calc(100dvh-5rem)]"
>

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                {{ __('Messages') }}
            </flux:heading>
            <flux:text class="mt-0.5 text-zinc-600 dark:text-zinc-400">
                {{ __('Support inbox for direct messages from customers.') }}
            </flux:text>
        </div>
    </div>

    {{-- ── Main content area ────────────────────────────────────────────── --}}
    <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden lg:flex-row lg:items-stretch">

        {{-- ── LEFT: Conversation list ─────────────────────────────────── --}}
        <div @class([
            'min-w-0 flex min-h-0 flex-1 flex-col gap-3',
            'hidden lg:flex' => $selectedConversationId,
            'flex' => !$selectedConversationId,
        ])>
            
            {{-- Status tabs --}}
            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <button
                        type="button"
                        wire:click="setStatusFilter('open')"
                        @class([
                            'rounded-md px-3 py-1.5 text-xs font-medium transition',
                            'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => $statusFilter === 'open',
                            'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $statusFilter !== 'open',
                        ])
                    >{{ __('Open') }}</button>
                    <button
                        type="button"
                        wire:click="setStatusFilter('closed')"
                        @class([
                            'rounded-md px-3 py-1.5 text-xs font-medium transition',
                            'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => $statusFilter === 'closed',
                            'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $statusFilter !== 'closed',
                        ])
                    >{{ __('Closed') }}</button>
                </div>
            </div>

            {{-- Conversation list --}}
            <div
                class="min-h-0 flex-1 overflow-y-auto overscroll-contain rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                wire:loading.class="pointer-events-none opacity-60"
                wire:target="setStatusFilter, gotoPage, previousPage, nextPage"
            >
                @if($this->conversations->isEmpty())
                    <div class="py-20 text-center">
                        <div class="mx-auto flex max-w-sm flex-col items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon name="chat-bubble-left-right" class="h-8 w-8 text-zinc-400" />
                            </div>
                            <div>
                                <p class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $statusFilter === 'open' ? __('No open conversations.') : __('No closed conversations.') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $statusFilter === 'open' ? __('You\'re all caught up!') : __('Closed conversations will appear here.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($this->conversations as $conversation)
                            @php
                                $isSelected = $selectedConversationId === $conversation->id;
                                $hasUnread = $conversation->messages->filter(fn($m) => !$m->is_read && $m->sender_id !== auth()->id())->isNotEmpty();
                            @endphp
                            <li>
                                <button 
                                    type="button" 
                                    wire:click="selectConversation({{ $conversation->id }})"
                                    @class([
                                        'flex w-full cursor-pointer items-start gap-4 p-4 text-left transition-colors',
                                        'border-l-2 border-sky-500 bg-sky-50/70 dark:bg-sky-950/20' => $isSelected,
                                        'bg-white hover:bg-zinc-50/80 dark:bg-zinc-900 dark:hover:bg-zinc-800/50' => !$isSelected,
                                    ])
                                >
                                    <div class="mt-1 relative">
                                        <flux:avatar :name="$conversation->user?->name" size="sm" />
                                        @if($hasUnread)
                                            <span class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-zinc-900"></span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <div @class([
                                                'truncate text-sm',
                                                'font-semibold text-zinc-900 dark:text-zinc-50' => $hasUnread,
                                                'font-medium text-zinc-900 dark:text-zinc-100' => !$hasUnread,
                                            ])>
                                                {{ $conversation->user?->name ?: __('Unknown User') }}
                                            </div>
                                            <div class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">
                                                @if($conversation->last_message_at)
                                                    @if($conversation->last_message_at->diffInDays() > 0)
                                                        {{ $conversation->last_message_at->format('M j') }}
                                                    @else
                                                        {{ $conversation->last_message_at->diffForHumans(short: true) }}
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-0.5 truncate text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ $conversation->subject ?: __('No subject') }}
                                        </div>
                                        <div @class([
                                            'mt-1 truncate text-sm',
                                            'text-zinc-900 font-medium dark:text-zinc-100' => $hasUnread,
                                            'text-zinc-500 dark:text-zinc-400' => !$hasUnread,
                                        ])>
                                            @php
                                                $lastMessage = $conversation->messages->last();
                                                $preview = $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->body, 60) : __('No messages yet');
                                                $prefix = $lastMessage && $lastMessage->sender_id === auth()->id() ? __('You: ') : '';
                                            @endphp
                                            {{ $prefix }}{{ $preview }}
                                        </div>
                                    </div>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    
                    @if($this->conversations->hasPages())
                        <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                            {{ $this->conversations->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- ── RIGHT: Thread panel ────────────────────────────────────────── --}}
        @if($selectedConversationId)
            <div
                class="flex min-h-0 w-full flex-1 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:w-[450px] lg:flex-none lg:self-stretch"
            >
                <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <h3 class="font-medium text-zinc-900 dark:text-zinc-50">{{ __('Conversation') }}</h3>
                    <button wire:click="closeThread" type="button" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                        <flux:icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>
                
                <livewire:admin.messaging.messaging-thread 
                    :conversation-id="$selectedConversationId" 
                    :key="'thread-'.$selectedConversationId" 
                />
            </div>
        @endif

    </div>
</div>
