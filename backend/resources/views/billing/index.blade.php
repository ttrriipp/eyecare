<x-layouts::app :title="__('Billing')">
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
                    {{ __('Billing') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Billing')],
                    ]"
                />
            </div>
        </div>

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('orders.billing.index') }}"
                class="grid gap-4 xl:grid-cols-2"
            >
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem] md:items-end">
                        <div class="min-w-0">
                            <label for="billing-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Search') }}
                            </label>
                            <div class="relative">
                                <input
                                    id="billing-search"
                                    name="search"
                                    type="text"
                                    data-preserve-focus="billing-search"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-14 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('Invoice #, OR #, order #…') }}"
                                    value="{{ $filters['search'] ?? '' }}"
                                    autocomplete="off"
                                    oninput="sessionStorage.setItem('preserveFocusInput', this.id); sessionStorage.setItem('preserveFocusPos', String(this.selectionStart ?? this.value.length)); sessionStorage.setItem(`liveSearchValue:${location.pathname}:${this.id}`, this.value); sessionStorage.setItem(`liveSearchPending:${location.pathname}:${this.id}`, '1'); clearTimeout(this.form._searchTimer); this.form._searchTimer = setTimeout(() => this.form.requestSubmit(), 250);"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-2xl font-semibold leading-none text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                    aria-label="{{ __('Clear search') }}"
                                    title="{{ __('Clear search') }}"
                                    onclick="const input = document.getElementById('billing-search'); input.value=''; sessionStorage.setItem('preserveFocusInput', input.id); sessionStorage.setItem('preserveFocusPos', '0'); sessionStorage.setItem(`liveSearchValue:${location.pathname}:${input.id}`, ''); sessionStorage.setItem(`liveSearchPending:${location.pathname}:${input.id}`, '1'); input.focus(); clearTimeout(this.form._searchTimer); this.form.requestSubmit();"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="billing-status" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Payment') }}
                            </label>
                            <select
                                id="billing-status"
                                name="payment_status"
                                onchange="this.form.submit()"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="">{{ __('All') }}</option>
                                @foreach($statuses as $status)
                                    <option
                                        value="{{ $status->value }}"
                                        @selected(($filters['payment_status'] ?? '') === $status->value)
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
                            <label for="billing-from" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('From') }}
                            </label>
                            <flux:input
                                id="billing-from"
                                type="date"
                                name="date_from"
                                :label="false"
                                value="{{ $filters['date_from'] ?? '' }}"
                            />
                        </div>
                        <div>
                            <label for="billing-to" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('To') }}
                            </label>
                            <flux:input
                                id="billing-to"
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
                                <flux:button :href="route('orders.billing.index')" variant="ghost" wire:navigate>
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
            @if($bills->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No invoices match your filters.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Invoice') }}</th>
                                <th class="px-4 py-3 hidden sm:table-cell">{{ __('Order') }}</th>
                                <th class="px-4 py-3 hidden md:table-cell">{{ __('Customer') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 hidden lg:table-cell">{{ __('Created') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($bills as $bill)
                                @php
                                    $ps = $bill->payment_status;
                                    $terminalRow = in_array($ps, [\App\Enums\PaymentStatus::Voided, \App\Enums\PaymentStatus::Refunded], true);
                                @endphp
                                <tr class="bg-white transition-colors dark:bg-zinc-900">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $bill->invoice_number }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden sm:table-cell text-zinc-600 dark:text-zinc-400">
                                        @if($bill->order)
                                            <a
                                                href="{{ route('orders.show', $bill->order) }}"
                                                class="text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-300"
                                                wire:navigate
                                            >
                                                {{ $bill->order->order_number }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell text-zinc-600 dark:text-zinc-400">
                                        @if($bill->order)
                                            @if($bill->order->isWalkIn())
                                                <span>{{ $bill->order->walk_in_name ?? '—' }}</span>
                                                <span
                                                    class="mt-1 inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200"
                                                >
                                                    {{ __('Walk-in') }}
                                                </span>
                                            @else
                                                {{ $bill->order->user?->name ?? '—' }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            @class([
                                                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                'bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-200' => $ps === \App\Enums\PaymentStatus::Unpaid,
                                                'bg-sky-100 text-sky-900 dark:bg-sky-950/80 dark:text-sky-200' => $ps === \App\Enums\PaymentStatus::PartiallyPaid,
                                                'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200' => $ps === \App\Enums\PaymentStatus::Paid,
                                                'bg-orange-100 text-orange-900 dark:bg-orange-950/80 dark:text-orange-200' => $ps === \App\Enums\PaymentStatus::PartiallyRefunded,
                                                'bg-violet-100 text-violet-900 dark:bg-violet-950/80 dark:text-violet-200' => $ps === \App\Enums\PaymentStatus::Refunded,
                                                'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' => $ps === \App\Enums\PaymentStatus::Voided,
                                            ])
                                        >
                                            {{ $bill->payment_status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                        <div>{{ \App\Support\Money::peso($bill->amount_paid) }} / {{ \App\Support\Money::peso($bill->amount) }}</div>
                                        @if(! $terminalRow && (float) $bill->balance_due > 0)
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                {{ __('Bal: :amount', ['amount' => \App\Support\Money::peso($bill->balance_due)]) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 hidden lg:table-cell">
                                        <time datetime="{{ $bill->created_at->toIso8601String() }}">
                                            {{ $bill->created_at->format('M j, Y') }}
                                        </time>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="eye"
                                                :href="route('orders.billing.show', $bill)"
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
                    {{ $bills->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
    <script>
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
