<x-layouts::app :title="__('Product feedback')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Product feedback') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Product feedback')],
                    ]"
                />
            </div>
        </div>

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('feedbacks.index') }}"
                class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end"
            >
                <div class="min-w-0 flex-1 lg:min-w-[12rem]">
                    <label for="fb-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Search') }}
                    </label>
                    <flux:input
                        id="fb-search"
                        name="search"
                        :label="false"
                        placeholder="{{ __('Customer, product, comment…') }}"
                        value="{{ $filters['search'] ?? '' }}"
                    />
                </div>

                <div class="w-full min-w-0 lg:w-auto lg:min-w-[14rem]">
                    <label for="fb-product" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Product') }}
                    </label>
                    <select
                        id="fb-product"
                        name="product_id"
                        onchange="this.form.submit()"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="">{{ __('All products') }}</option>
                        @foreach($products as $product)
                            <option
                                value="{{ $product->id }}"
                                @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)
                            >
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full min-w-0 lg:w-auto lg:min-w-[10rem]">
                    <label for="fb-rating" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Rating') }}
                    </label>
                    <select
                        id="fb-rating"
                        name="rating"
                        onchange="this.form.submit()"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="">{{ __('Any') }}</option>
                        @foreach([5, 4, 3, 2, 1] as $r)
                            <option value="{{ $r }}" @selected((string) ($filters['rating'] ?? '') === (string) $r)>
                                {{ $r }} {{ __('stars') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full min-w-0 lg:w-auto lg:min-w-[11rem]">
                    <label for="fb-type" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Type') }}
                    </label>
                    <select
                        id="fb-type"
                        name="feedback_type"
                        onchange="this.form.submit()"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="">{{ __('All types') }}</option>
                        <option value="product" @selected(($filters['feedback_type'] ?? '') === 'product')>{{ __('Product') }}</option>
                        <option value="appointment" @selected(($filters['feedback_type'] ?? '') === 'appointment')>{{ __('Appointment') }}</option>
                        <option value="service" @selected(($filters['feedback_type'] ?? '') === 'service')>{{ __('Service') }}</option>
                    </select>
                </div>

                <div class="flex gap-2 lg:ml-auto">
                    <flux:button :href="route('feedbacks.index')" variant="ghost" wire:navigate>
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @if($feedbacks->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No reviews match your filters.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Product') }}</th>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('Type') }}</th>
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('On product page') }}</th>
                                <th class="px-4 py-3">{{ __('Customer') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Rating') }}</th>
                                <th class="px-4 py-3 hidden lg:table-cell">{{ __('Comment') }}</th>
                                <th class="px-4 py-3 hidden sm:table-cell whitespace-nowrap">{{ __('Date') }}</th>
                                <th class="px-3 py-3 text-center whitespace-nowrap">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($feedbacks as $fb)
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $fb->product?->name ?? '—' }}
                                        </div>
                                        @if($fb->product?->sku)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-500">
                                                {{ $fb->product->sku }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top whitespace-nowrap text-xs text-zinc-600 dark:text-zinc-400">
                                        @switch($fb->feedback_type?->value ?? $fb->feedback_type)
                                            @case('appointment')
                                                {{ __('Appointment') }}
                                                @if($fb->appointment_id)
                                                    <span class="block text-zinc-500">#{{ $fb->appointment_id }}</span>
                                                @endif
                                                @break
                                            @case('service')
                                                {{ __('Service') }}
                                                @break
                                            @default
                                                {{ __('Product') }}
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 align-top whitespace-nowrap">
                                        @if($fb->is_visible)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200">{{ __('Visible') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">{{ __('Hidden') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="text-zinc-900 dark:text-zinc-100">
                                            {{ $fb->user?->name ?? '—' }}
                                        </div>
                                        @if($fb->user?->email)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-500">
                                                {{ $fb->user->email }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top text-center tabular-nums">
                                        <span class="inline-flex items-center justify-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900 dark:bg-amber-950/80 dark:text-amber-200">
                                            {{ $fb->rating }}/5
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 align-top text-zinc-600 dark:text-zinc-400 hidden lg:table-cell max-w-md">
                                        @if($fb->comment)
                                            <p class="line-clamp-3 whitespace-pre-wrap break-words">{{ $fb->comment }}</p>
                                        @else
                                            <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top text-zinc-600 dark:text-zinc-400 hidden sm:table-cell whitespace-nowrap text-xs">
                                        {{ $fb->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    </td>
                                    <td class="px-3 py-3 align-middle text-center">
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                icon="ellipsis-vertical"
                                                class="shrink-0"
                                                title="{{ __('Actions') }}"
                                            >
                                                <span class="sr-only">{{ __('Actions') }}</span>
                                            </flux:button>

                                            <flux:menu>
                                                <flux:modal.trigger name="reply-feedback-{{ $fb->id }}">
                                                    <flux:menu.item as="button" type="button" icon="chat-bubble-left-right">
                                                        {{ __('Reply') }}
                                                    </flux:menu.item>
                                                </flux:modal.trigger>

                                                @if($fb->is_visible)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('feedbacks.visibility', $fb) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                                                        class="contents"
                                                    >
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="is_visible" value="0">
                                                        <flux:menu.item as="button" type="submit" icon="eye-slash">
                                                            {{ __('Hide from product page') }}
                                                        </flux:menu.item>
                                                    </form>
                                                @else
                                                    <form
                                                        method="POST"
                                                        action="{{ route('feedbacks.visibility', $fb) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                                                        class="contents"
                                                    >
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="is_visible" value="1">
                                                        <flux:menu.item as="button" type="submit" icon="eye">
                                                            {{ __('Show on product page') }}
                                                        </flux:menu.item>
                                                    </form>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>
                                <tr class="bg-zinc-50/80 dark:bg-zinc-950/40 lg:hidden">
                                    <td colspan="8" class="px-4 pb-3 pt-0 text-xs text-zinc-600 dark:text-zinc-400">
                                        @if($fb->comment)
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('Comment') }}:</span>
                                            {{ \Illuminate\Support\Str::limit($fb->comment, 200) }}
                                        @else
                                            <span class="text-zinc-400">{{ __('No comment') }}</span>
                                        @endif
                                        <div class="mt-1 text-zinc-500 sm:hidden">
                                            {{ $fb->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($feedbacks as $fb)
                    <flux:modal name="reply-feedback-{{ $fb->id }}" focusable class="max-w-2xl">
                        <form
                            method="POST"
                            action="{{ route('feedbacks.reply', $fb) }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
                            class="space-y-4"
                        >
                            @csrf
                            @method('PUT')
                            <div class="pr-8">
                                <flux:heading size="lg">{{ __('Clinic reply') }}</flux:heading>
                                <flux:subheading class="mt-1">
                                    {{ $fb->product?->name ?? '—' }} · {{ $fb->user?->name ?? '—' }}
                                </flux:subheading>
                            </div>

                            <div
                                class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-900/80"
                            >
                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    {{ __('Customer review') }}
                                </p>
                                <p class="mt-1 text-zinc-800 dark:text-zinc-200">
                                    {{ __('Rating') }}: {{ $fb->rating }}/5
                                </p>
                                @if(filled($fb->comment))
                                    <p class="mt-2 whitespace-pre-wrap text-zinc-700 dark:text-zinc-300">{{ $fb->comment }}</p>
                                @else
                                    <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('No written comment.') }}</p>
                                @endif
                            </div>

                            @if(filled($fb->admin_reply) && ($fb->moderated_at || $fb->moderator))
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @if($fb->moderator)
                                        {{ __('Last saved by :name', ['name' => $fb->moderator->name]) }}
                                    @endif
                                    @if($fb->moderated_at)
                                        {{ $fb->moderator ? ' · ' : '' }}{{ $fb->moderated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    @endif
                                </p>
                            @endif

                            <div>
                                <label for="admin-reply-{{ $fb->id }}" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Your reply to the customer') }}
                                </label>
                                <flux:textarea
                                    id="admin-reply-{{ $fb->id }}"
                                    name="admin_reply"
                                    rows="6"
                                    placeholder="{{ __('Thank the customer or address their feedback…') }}"
                                >{{ $fb->admin_reply }}</flux:textarea>
                                @error('admin_reply')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Leave empty and save to remove the reply.') }}
                                </p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary">{{ __('Save reply') }}</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endforeach

                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $feedbacks->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
