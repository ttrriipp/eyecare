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

                <div class="flex gap-2 lg:ml-auto">
                    <flux:button type="submit" variant="primary">
                        {{ __('Apply') }}
                    </flux:button>
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
                                <th class="px-4 py-3">{{ __('Customer') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Rating') }}</th>
                                <th class="px-4 py-3 hidden lg:table-cell">{{ __('Comment') }}</th>
                                <th class="px-4 py-3 hidden sm:table-cell whitespace-nowrap">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
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
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex justify-center">
                                            @if(auth()->user()?->isAdmin())
                                                <flux:modal.trigger name="confirm-delete-feedback-{{ $fb->id }}">
                                                    <flux:button type="button" size="sm" variant="danger" icon="trash">
                                                        <span class="sr-only">{{ __('Delete') }}</span>
                                                    </flux:button>
                                                </flux:modal.trigger>
                                            @else
                                                <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-zinc-50/80 dark:bg-zinc-950/40 lg:hidden">
                                    <td colspan="6" class="px-4 pb-3 pt-0 text-xs text-zinc-600 dark:text-zinc-400">
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

                @if(auth()->user()?->isAdmin())
                    @foreach($feedbacks as $fb)
                        <flux:modal name="confirm-delete-feedback-{{ $fb->id }}" focusable class="max-w-xl">
                            <div class="space-y-2">
                                <flux:heading size="lg">
                                    {{ __('Are you sure you want to delete this feedback?') }}
                                </flux:heading>
                                <flux:subheading>
                                    {{ __('This action cannot be undone.') }}
                                </flux:subheading>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <form method="POST" action="{{ route('feedbacks.destroy', $fb) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button>
                                </form>
                            </div>
                        </flux:modal>
                    @endforeach
                @endif

                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $feedbacks->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
