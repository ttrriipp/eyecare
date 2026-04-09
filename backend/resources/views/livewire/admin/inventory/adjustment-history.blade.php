<div class="flex flex-col gap-4">

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                {{ __('Inventory Adjustment History') }}
            </flux:heading>
            <flux:text class="mt-0.5 text-zinc-600 dark:text-zinc-400">
                {{ __('A full audit trail of all stock changes.') }}
            </flux:text>
        </div>
        <flux:button
            wire:click="export"
            wire:loading.attr="disabled"
            variant="ghost"
            icon="arrow-down-tray"
        >
            <span wire:loading.remove wire:target="export">{{ __('Export CSV') }}</span>
            <span wire:loading wire:target="export">{{ __('Exporting…') }}</span>
        </flux:button>
    </div>

    {{-- ── Filters ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

        <div class="min-w-[180px] flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by product name…') }}"
                icon="magnifying-glass"
                :label="false"
                autocomplete="off"
            />
        </div>

        <div class="min-w-[140px]">
            <select wire:model.live="reason_filter"
                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            >
                <option value="">{{ __('All reasons') }}</option>
                <option value="restock">{{ __('Restock') }}</option>
                <option value="sale_correction">{{ __('Sale correction') }}</option>
                <option value="damaged">{{ __('Damaged') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
                <option value="returned">{{ __('Returned') }}</option>
                <option value="initial_count">{{ __('Initial count') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </select>
        </div>

        <div class="min-w-[140px]">
            <select wire:model.live="adjusted_by"
                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            >
                <option value="">{{ __('All staff') }}</option>
                @foreach($this->staffList as $staff)
                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <input wire:model.live="date_from" type="date"
                class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            >
            <span class="text-xs text-zinc-400">—</span>
            <input wire:model.live="date_to" type="date"
                class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            >
        </div>

        @if($search || $reason_filter || $adjusted_by || $date_from || $date_to || $product_id)
            <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                {{ __('Clear filters') }}
            </button>
        @endif
    </div>

    {{-- Product filter banner (when product_id is set via URL) --}}
    @if($product_id)
        <div class="flex items-center justify-between rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm text-sky-800 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
            <span>{{ __('Showing adjustments for one product only.') }}</span>
            <button type="button" wire:click="$set('product_id', null)" class="text-sky-600 hover:underline dark:text-sky-400">
                {{ __('Clear') }}
            </button>
        </div>
    @endif

    {{-- ── Table ────────────────────────────────────────────────────────── --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
         wire:loading.class="pointer-events-none opacity-60">

        @if($this->adjustments->isEmpty())
            <div class="py-16 text-center">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ ($search || $reason_filter || $adjusted_by || $date_from || $date_to || $product_id)
                        ? __('No adjustments match your filters.')
                        : __('No inventory adjustments recorded yet.') }}
                </p>
            </div>
        @else
            <table class="w-full min-w-[56rem] text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400">
                        <th class="px-4 py-3">{{ __('Date & time') }}</th>
                        <th class="px-4 py-3">{{ __('Product / Variant') }}</th>
                        <th class="px-4 py-3">{{ __('Reason') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Change') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('New qty') }}</th>
                        <th class="px-4 py-3">{{ __('Adjusted by') }}</th>
                        <th class="px-4 py-3">{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($this->adjustments as $adj)
                        @php
                            $variant  = $adj->inventory?->productVariant;
                            $adjProd  = $variant?->product;
                            $unit     = $adjProd?->category?->stock_unit ?? 'units';
                            [$reason, $notes] = str_contains((string) $adj->reason, ': ')
                                ? explode(': ', $adj->reason, 2)
                                : [$adj->reason, ''];
                        @endphp
                        <tr class="bg-white hover:bg-zinc-50/60 dark:bg-zinc-900 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-xs tabular-nums text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $adj->created_at->format('M j, Y') }}<br>
                                <span class="text-zinc-400 dark:text-zinc-500">{{ $adj->created_at->format('g:i A') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-zinc-900 dark:text-zinc-50">{{ $adjProd?->name ?? '—' }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->variantLabel($variant) }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium
                                    {{ $this->reasonColor($adj->reason) }}">
                                    {{ $this->reasonLabel($adj->reason) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-bold tabular-nums
                                    {{ $adj->delta >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $adj->delta >= 0 ? '+' : '' }}{{ $adj->delta }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $adj->quantity_after }}</span>
                                <span class="ml-1 text-xs text-zinc-400">{{ $unit }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($adj->adjustedBy)
                                    <p class="text-sm text-zinc-800 dark:text-zinc-200">{{ $adj->adjustedBy->name }}</p>
                                    <p class="text-[11px] text-zinc-400">{{ $adj->adjustedBy->role?->label() ?? '' }}</p>
                                @else
                                    <span class="text-xs text-zinc-400">{{ __('System') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if(filled($notes))
                                    <span class="cursor-help truncate text-xs italic text-zinc-500 dark:text-zinc-400"
                                          title="{{ $notes }}">
                                        {{ Str::limit($notes, 40) }}
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                {{ $this->adjustments->links() }}
            </div>
        @endif
    </div>

</div>
