<div class="flex min-h-0 flex-1 flex-col bg-zinc-50/50 dark:bg-zinc-900/50">

    {{-- Messages list --}}
    <div
        id="messages-container"
        class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto overscroll-contain p-4"
    >
        @if($this->threadMessages->isEmpty())
            <div class="m-auto flex flex-col items-center justify-center text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="chat-bubble-bottom-center-text" class="h-6 w-6 text-zinc-400" />
                </div>
                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('No messages to show') }}</h3>
                <p class="mt-1 max-w-xs text-sm text-zinc-500 dark:text-zinc-400">{{ __('Wait for the user to send a message, or send one yourself to start the conversation.') }}</p>
            </div>
        @else
            @foreach($this->threadMessages as $message)
                @php
                    $isCustomer = $message->sender?->role === \App\Enums\UserRole::Customer;
                    // Customer messages are left-aligned, staff/admin messages are right-aligned.
                @endphp

                <div
                    wire:key="msg-{{ $message->id }}"
                    @class([
                        'flex w-full',
                        'justify-start' => $isCustomer || $message->sender_id === null,
                        'justify-end' => !$isCustomer && $message->sender_id !== null,
                    ])
                >
                    <div @class([
                        'flex max-w-[85%] flex-col gap-1',
                        'items-start' => $isCustomer || $message->sender_id === null,
                        'items-end' => !$isCustomer && $message->sender_id !== null,
                    ])>
                        <div class="flex items-center gap-2 px-1">
                            <span class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                                {{ $message->sender?->name ?: __('Optical Shop') }}
                            </span>
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">
                                @if($message->created_at->diffInHours() < 24)
                                    {{ $message->created_at->format('g:i A') }}
                                @else
                                    {{ $message->created_at->format('M j, g:i A') }}
                                @endif
                            </span>
                        </div>

                        <div @class([
                            'rounded-2xl px-4 py-2 text-sm shadow-sm break-words [word-break:break-word]',
                            // Incoming: explicit dark text on light bubble (light mode) and light text on dark bubble (dark mode)
                            'rounded-tl-none border border-zinc-200 bg-zinc-100 !text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:!text-zinc-50' => $isCustomer || $message->sender_id === null,
                            // Outgoing (staff): always white on solid sky (both themes)
                            'rounded-tr-none bg-sky-600 !text-white dark:bg-sky-600' => !$isCustomer && $message->sender_id !== null,
                        ])>
                            <span class="block whitespace-pre-wrap">{!! nl2br(e($message->body)) !!}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Input area --}}
    <div class="shrink-0 border-t border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="sendMessage" class="flex flex-col gap-3">
            <div class="relative">
                <flux:textarea
                    wire:model.live.debounce.150ms="body"
                    placeholder="{{ __('Type your message...') }}"
                    rows="3"
                    resize="none"
                    class="w-full pr-12 text-sm text-zinc-900 placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                />
            </div>

            <div class="flex items-center justify-end">
                <flux:button
                    type="submit"
                    variant="primary"
                    size="sm"
                    :disabled="empty(trim($body))"
                >
                    {{ __('Send') }}
                </flux:button>
            </div>
        </form>
    </div>

    @script
    <script>
        const scrollContainer = document.getElementById('messages-container');

        // Auto-scroll on initial load
        if (scrollContainer) {
            scrollContainer.scrollTop = scrollContainer.scrollHeight;
        }

        // Auto-scroll after sending a message
        $wire.on('scroll-to-bottom', () => {
            setTimeout(() => {
                if (scrollContainer) {
                    scrollContainer.scrollTo({
                        top: scrollContainer.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            }, 50);
        });
    </script>
    @endscript
</div>
