<x-layouts::app :title="__('Order Status History')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Order Status History') }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Staff and admin actions: creating orders and changing order status.') }}
                </flux:text>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Orders'), 'href' => route('orders.index')],
                        ['label' => __('Order Status History')],
                    ]"
                />
            </div>
        </div>

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('orders.status-history.index') }}"
                class="grid gap-4 xl:grid-cols-2"
            >
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem] md:items-end">
                        <div class="min-w-0">
                            <label for="history-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Search') }}
                            </label>
                            <div class="relative">
                                <input
                                    id="history-search"
                                    name="search"
                                    type="text"
                                    data-preserve-focus="history-search"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 pr-14 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('Order #, staff name, email…') }}"
                                    value="{{ $filters['search'] ?? '' }}"
                                    autocomplete="off"
                                    oninput="sessionStorage.setItem('preserveFocusInput', this.id); sessionStorage.setItem('preserveFocusPos', String(this.selectionStart ?? this.value.length)); sessionStorage.setItem(`liveSearchValue:${location.pathname}:${this.id}`, this.value); sessionStorage.setItem(`liveSearchPending:${location.pathname}:${this.id}`, '1'); clearTimeout(this.form._searchTimer); this.form._searchTimer = setTimeout(() => this.form.requestSubmit(), 250);"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-2xl font-semibold leading-none text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                    aria-label="{{ __('Clear search') }}"
                                    title="{{ __('Clear search') }}"
                                    onclick="const input = document.getElementById('history-search'); input.value=''; sessionStorage.setItem('preserveFocusInput', input.id); sessionStorage.setItem('preserveFocusPos', '0'); sessionStorage.setItem(`liveSearchValue:${location.pathname}:${input.id}`, ''); sessionStorage.setItem(`liveSearchPending:${location.pathname}:${input.id}`, '1'); input.focus(); clearTimeout(this.form._searchTimer); this.form.requestSubmit();"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="history-actor" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Staff') }}
                            </label>
                            <select
                                id="history-actor"
                                name="actor_user_id"
                                onchange="this.form.submit()"
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="">{{ __('All staff') }}</option>
                                @foreach($staffUsers as $u)
                                    <option
                                        value="{{ $u->id }}"
                                        @selected((string) ($filters['actor_user_id'] ?? '') === (string) $u->id)
                                    >
                                        {{ $u->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
                        <div>
                            <label for="history-from" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('From') }}
                            </label>
                            <flux:input
                                id="history-from"
                                type="date"
                                name="date_from"
                                :label="false"
                                value="{{ $filters['date_from'] ?? '' }}"
                            />
                        </div>
                        <div>
                            <label for="history-to" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('To') }}
                            </label>
                            <flux:input
                                id="history-to"
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
                                <flux:button variant="ghost" :href="route('orders.status-history.index')" wire:navigate>
                                    {{ __('Reset') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('When') }}</th>
                            <th class="px-4 py-3">{{ __('Staff') }}</th>
                            <th class="px-4 py-3">{{ __('Order') }}</th>
                            <th class="px-4 py-3">{{ __('Activity') }}</th>
                            <th class="px-4 py-3 hidden md:table-cell">{{ __('From') }}</th>
                            <th class="px-4 py-3 hidden md:table-cell">{{ __('To') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($entries as $row)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-900 dark:text-zinc-100">
                                    <time datetime="{{ $row->created_at->toIso8601String() }}">
                                        {{ $row->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    </time>
                                </td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                    {{ $row->actor?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($row->order)
                                        <a
                                            href="{{ route('orders.show', $row->order) }}"
                                            class="font-mono font-medium text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-800 dark:text-sky-400"
                                            wire:navigate
                                        >
                                            {{ $row->order->order_number }}
                                        </a>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    @if($row->action === \App\Models\OrderStatusHistory::ACTION_CREATED)
                                        {{ __('Created order') }}
                                    @else
                                        {{ __('Updated status') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-zinc-600 dark:text-zinc-400">
                                    {{ $row->from_status?->label() ?? '—' }}
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell text-zinc-900 dark:text-zinc-100">
                                    {{ $row->to_status->label() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                    {{ __('No activity recorded yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($entries->hasPages())
                <div class="border-t border-zinc-200 px-2 py-3 text-sm dark:border-zinc-700">
                    {{ $entries->withQueryString()->links() }}
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
