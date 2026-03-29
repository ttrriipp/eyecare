<x-layouts::app :title="__('Inventory')">
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
                    {{ __('Inventory') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Inventory')],
                    ]"
                />
            </div>
        </div>

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('inventory.index') }}"
                class="flex flex-col gap-4 lg:flex-row lg:items-end"
            >
                <div class="min-w-0 flex-1">
                    <label for="inv-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Search') }}
                    </label>
                    <flux:input
                        id="inv-search"
                        name="search"
                        :label="false"
                        placeholder="{{ __('Product name, SKU, brand…') }}"
                        value="{{ $filters['search'] ?? '' }}"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-4 lg:ml-auto">
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input
                            type="checkbox"
                            name="low_stock"
                            value="1"
                            @checked($filters['low_stock'] ?? false)
                            class="h-4 w-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950"
                        >
                        <span>{{ __('Low stock only') }}</span>
                    </label>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">
                            {{ __('Apply') }}
                        </flux:button>
                        <flux:button :href="route('inventory.index')" variant="ghost" wire:navigate>
                            {{ __('Reset') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @if($products->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No products match your filters.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Product') }}</th>
                                <th class="px-4 py-3">{{ __('SKU') }}</th>
                                <th class="px-4 py-3 hidden md:table-cell">{{ __('Category') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('On hand') }}</th>
                                <th class="px-4 py-3 text-center hidden sm:table-cell">{{ __('Reorder at') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Stock') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($products as $product)
                                @php
                                    $inv = $product->inventory;
                                    $qty = $inv?->quantity ?? 0;
                                    $reorder = $inv?->reorder_level ?? 0;
                                    $low = $inv && $inv->isLowStock();
                                    $catalogActive = (bool) $product->is_active;
                                @endphp
                                <tr
                                    @class([
                                        'transition-colors',
                                        'bg-white dark:bg-zinc-900' => $catalogActive,
                                        'bg-zinc-100/80 dark:bg-zinc-950/60' => ! $catalogActive,
                                    ])
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $product->name }}
                                        </div>
                                        @if($product->brand)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-500">
                                                {{ $product->brand }}
                                            </div>
                                        @endif
                                        <div class="mt-1.5">
                                            @if($catalogActive)
                                                <span
                                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200"
                                                >
                                                    {{ __('Active') }}
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200"
                                                >
                                                    {{ __('Inactive') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        {{ $product->sku ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 hidden md:table-cell text-zinc-600 dark:text-zinc-400">
                                        {{ $product->category?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                        {{ $qty }}
                                    </td>
                                    <td class="px-4 py-3 tabular-nums text-zinc-600 dark:text-zinc-400 hidden sm:table-cell">
                                        <div class="flex justify-center">{{ $reorder }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center">
                                            @if(! $inv)
                                                <span
                                                    class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-950/80 dark:text-amber-200"
                                                >
                                                    {{ __('Not initialized') }}
                                                </span>
                                            @elseif($low)
                                                <span
                                                    class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-950/80 dark:text-amber-200"
                                                >
                                                    {{ __('Low stock') }}
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200"
                                                >
                                                    {{ __('OK') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center">
                                            @if(auth()->user()?->isAdmin())
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="pencil-square"
                                                    :href="route('inventory.edit', $product)"
                                                    wire:navigate
                                                >
                                                    <span class="sr-only">{{ __('Adjust stock') }}</span>
                                                </flux:button>
                                            @else
                                                <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
