<x-layouts::app :title="__('Ordering')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Ordering') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Ordering')],
                    ]"
                />
            </div>
            @if(auth()->user()?->isAdminOrStaff())
                <flux:button variant="primary" icon="plus" :href="route('orders.create')" wire:navigate>
                    {{ __('Create order') }}
                </flux:button>
            @endif
        </div>

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('orders.index') }}"
                class="flex flex-col gap-4 xl:flex-row xl:flex-wrap xl:items-end"
            >
                <div class="min-w-0 flex-1">
                    <label for="orders-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Search') }}
                    </label>
                    <flux:input
                        id="orders-search"
                        name="search"
                        :label="false"
                        placeholder="{{ __('Order #, customer name, phone, email…') }}"
                        value="{{ $filters['search'] ?? '' }}"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <label for="orders-status" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Status') }}
                    </label>
                    <select
                        id="orders-status"
                        name="status"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach($statuses as $status)
                            <option
                                value="{{ $status->value }}"
                                @selected(($filters['status'] ?? '') === $status->value)
                            >
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid w-full gap-4 sm:grid-cols-2 lg:w-auto lg:min-w-[12rem]">
                    <div>
                        <label for="orders-from" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('From') }}
                        </label>
                        <flux:input
                            id="orders-from"
                            type="date"
                            name="date_from"
                            :label="false"
                            value="{{ $filters['date_from'] ?? '' }}"
                        />
                    </div>
                    <div>
                        <label for="orders-to" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('To') }}
                        </label>
                        <flux:input
                            id="orders-to"
                            type="date"
                            name="date_to"
                            :label="false"
                            value="{{ $filters['date_to'] ?? '' }}"
                        />
                    </div>
                </div>

                <div class="flex gap-2 lg:ml-auto">
                    <flux:button type="submit" variant="primary">
                        {{ __('Apply') }}
                    </flux:button>
                    <flux:button :href="route('orders.index')" variant="ghost" wire:navigate>
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @if($orders->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No orders match your filters.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Order') }}</th>
                                <th class="px-4 py-3 hidden sm:table-cell">{{ __('Customer') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Total') }}</th>
                                <th class="px-4 py-3 hidden md:table-cell">{{ __('Placed') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($orders as $order)
                                <tr class="bg-white transition-colors dark:bg-zinc-900">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $order->order_number }}
                                        </div>
                                        @if($order->notes)
                                            <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500 dark:text-zinc-500">
                                                {{ $order->notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 hidden sm:table-cell text-zinc-600 dark:text-zinc-400">
                                        @if($order->isWalkIn())
                                            <div>{{ $order->walk_in_name ?? '—' }}</div>
                                            @if($order->walk_in_phone)
                                                <div class="text-xs tabular-nums">{{ $order->walk_in_phone }}</div>
                                            @endif
                                            <span
                                                class="mt-1 inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200"
                                            >
                                                {{ __('Walk-in') }}
                                            </span>
                                        @else
                                            <div>{{ $order->user?->name ?? '—' }}</div>
                                            @if($order->user?->email)
                                                <div class="text-xs">{{ $order->user->email }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $s = $order->status;
                                        @endphp
                                        <span
                                            @class([
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-200' => $s === \App\Enums\OrderStatus::Pending,
                                                'bg-sky-100 text-sky-900 dark:bg-sky-950/80 dark:text-sky-200' => $s === \App\Enums\OrderStatus::Confirmed,
                                                'bg-violet-100 text-violet-900 dark:bg-violet-950/80 dark:text-violet-200' => $s === \App\Enums\OrderStatus::ReadyForPickup,
                                                'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200' => $s === \App\Enums\OrderStatus::Completed,
                                                'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' => $s === \App\Enums\OrderStatus::Cancelled,
                                            ])
                                        >
                                            {{ $order->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                        {{ number_format((float) $order->total_amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 hidden md:table-cell">
                                        <time datetime="{{ $order->created_at->toIso8601String() }}">
                                            {{ $order->created_at->format('M j, Y g:i A') }}
                                        </time>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="eye"
                                                :href="route('orders.show', $order)"
                                                wire:navigate
                                            >
                                                <span class="sr-only">{{ __('View') }}</span>
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $orders->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
