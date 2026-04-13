<x-layouts::app :title="__('Orders')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        @error('status')
            <div
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-100"
                role="alert"
            >
                {{ $message }}
            </div>
        @enderror

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Orders') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Orders')],
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
                class="grid gap-4 xl:grid-cols-2"
            >
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem] md:items-end">
                        <div class="min-w-0">
                            <label for="orders-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Search') }}
                            </label>
                            <div class="relative">
                                <input
                                    id="orders-search"
                                    name="search"
                                    type="text"
                                    data-preserve-focus="orders-search"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-14 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('Order #, customer name, phone, email…') }}"
                                    value="{{ $filters['search'] ?? '' }}"
                                    autocomplete="off"
                                    oninput="sessionStorage.setItem('preserveFocusInput', this.id); sessionStorage.setItem('preserveFocusPos', String(this.selectionStart ?? this.value.length)); sessionStorage.setItem(`liveSearchValue:${location.pathname}:${this.id}`, this.value); sessionStorage.setItem(`liveSearchPending:${location.pathname}:${this.id}`, '1'); clearTimeout(this.form._searchTimer); this.form._searchTimer = setTimeout(() => this.form.requestSubmit(), 250);"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-2xl font-semibold leading-none text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                    aria-label="{{ __('Clear search') }}"
                                    title="{{ __('Clear search') }}"
                                    onclick="const input = document.getElementById('orders-search'); input.value=''; sessionStorage.setItem('preserveFocusInput', input.id); sessionStorage.setItem('preserveFocusPos', '0'); sessionStorage.setItem(`liveSearchValue:${location.pathname}:${input.id}`, ''); sessionStorage.setItem(`liveSearchPending:${location.pathname}:${input.id}`, '1'); input.focus(); clearTimeout(this.form._searchTimer); this.form.requestSubmit();"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="orders-status" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Status') }}
                            </label>
                            <select
                                id="orders-status"
                                name="status"
                                onchange="this.form.submit()"
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
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
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
                        <div class="sm:col-span-2">
                            <div class="flex justify-end gap-2">
                                <flux:button type="submit" variant="primary">
                                    {{ __('Apply') }}
                                </flux:button>
                                <flux:button :href="route('orders.index')" variant="ghost" wire:navigate>
                                    {{ __('Reset') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
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
                                <th class="px-3 py-3 text-center whitespace-nowrap">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($orders as $order)
                                @php
                                    $orderNextStatuses = auth()->user()?->isAdminOrStaff()
                                        ? collect(\App\Enums\OrderStatus::cases())->filter(
                                            fn (\App\Enums\OrderStatus $st) => $order->status->canTransitionTo($st),
                                        )
                                        : collect();
                                @endphp
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
                                        {{ \App\Support\Money::peso($order->total_amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 hidden md:table-cell">
                                        <time datetime="{{ $order->created_at->toIso8601String() }}">
                                            {{ $order->created_at->format('M j, Y g:i A') }}
                                        </time>
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
                                                <flux:menu.item
                                                    :href="route('orders.show', $order)"
                                                    icon="eye"
                                                    wire:navigate
                                                >
                                                    {{ __('View') }}
                                                </flux:menu.item>
                                                @if($orderNextStatuses->isNotEmpty())
                                                    <flux:modal.trigger name="update-order-status-{{ $order->id }}">
                                                        <flux:menu.item as="button" type="button" icon="arrow-path">
                                                            {{ __('Update status') }}
                                                        </flux:menu.item>
                                                    </flux:modal.trigger>
                                                @endif
                                            </flux:menu>
                                        </flux:dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(auth()->user()?->isAdminOrStaff())
                    @foreach($orders as $order)
                        @php
                            $nextStatuses = collect(\App\Enums\OrderStatus::cases())->filter(
                                fn (\App\Enums\OrderStatus $st) => $order->status->canTransitionTo($st),
                            );
                        @endphp
                        @if($nextStatuses->isNotEmpty())
                            <flux:modal name="update-order-status-{{ $order->id }}" focusable class="max-w-xl">
                                <form
                                    method="POST"
                                    action="{{ route('orders.status.update', $order) }}"
                                    class="order-status-update-form space-y-4"
                                    data-cancel-prompt="{{ e(__('Cancel this order? Unpaid bills will be voided and paid bills refunded when applicable.')) }}"
                                >
                                    @csrf
                                    @method('PUT')
                                    <div class="pr-8">
                                        <flux:heading size="lg">{{ __('Update status') }}</flux:heading>
                                        <flux:subheading class="mt-1">
                                            {{ $order->order_number }}
                                        </flux:subheading>
                                        <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ __('Move the order through pickup, or cancel when needed.') }}
                                        </flux:text>
                                    </div>
                                    <div>
                                        <label
                                            for="order-status-{{ $order->id }}"
                                            class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                                        >
                                            {{ __('New status') }}
                                        </label>
                                        <select
                                            id="order-status-{{ $order->id }}"
                                            name="status"
                                            required
                                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                        >
                                            @foreach($nextStatuses as $st)
                                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button type="submit" variant="primary">{{ __('Apply') }}</flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        @endif
                    @endforeach
                @endif

                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $orders->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
    <script>
        document.querySelectorAll('form.order-status-update-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const sel = form.querySelector('select[name="status"]');
                if (!sel || sel.value !== @json(\App\Enums\OrderStatus::Cancelled->value)) {
                    return;
                }
                const msg = form.getAttribute('data-cancel-prompt') || '';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                }
            });
        });

        (() => {
            const inputId = sessionStorage.getItem('preserveFocusInput');
            if (!inputId) return;

            const input = document.getElementById(inputId);
            if (!input || !(input instanceof HTMLInputElement)) return;

            const valueKey = `liveSearchValue:${location.pathname}:${inputId}`;
            const pendingKey = `liveSearchPending:${location.pathname}:${inputId}`;
            const pending = sessionStorage.getItem(pendingKey) === '1';
            const latestValue = sessionStorage.getItem(valueKey);

            if (pending && latestValue !== null && input.value !== latestValue) {
                input.value = latestValue;
                const reconcilePos = Math.max(0, Math.min(Number(sessionStorage.getItem('preserveFocusPos') ?? latestValue.length), latestValue.length));

                requestAnimationFrame(() => {
                    input.focus({ preventScroll: true });
                    input.setSelectionRange(reconcilePos, reconcilePos);
                });

                if (input.form) {
                    clearTimeout(input.form._searchTimer);
                    input.form._searchTimer = setTimeout(() => input.form.requestSubmit(), 0);
                }
                return;
            }

            sessionStorage.removeItem(pendingKey);

            const posRaw = sessionStorage.getItem('preserveFocusPos');
            const pos = Number.isFinite(Number(posRaw)) ? Number(posRaw) : input.value.length;
            const safePos = Math.max(0, Math.min(pos, input.value.length));

            requestAnimationFrame(() => {
                input.focus({ preventScroll: true });
                input.setSelectionRange(safePos, safePos);
            });
        })();
    </script>
</x-layouts::app>
