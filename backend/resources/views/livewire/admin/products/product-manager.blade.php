<div class="flex h-full w-full flex-1 flex-col gap-4">

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                {{ __('Products') }}
            </flux:heading>
            <flux:text class="mt-0.5 text-zinc-600 dark:text-zinc-400">
                {{ __('Manage your product catalog and stock levels.') }}
            </flux:text>
        </div>
        @if(auth()->user()?->isAdmin())
            <flux:button wire:click="openAdd" variant="primary" icon="plus" class="shrink-0">
                {{ __('Add product') }}
            </flux:button>
        @endif
    </div>

    {{-- ── Main content area ────────────────────────────────────────────── --}}
    <div class="flex min-h-0 flex-1 gap-4">

        {{-- ── LEFT: Product list ──────────────────────────────────────── --}}
        <div class="flex min-w-0 flex-1 flex-col gap-3">

            {{-- Search + filters --}}
            <div class="flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">

                <div class="min-w-0 flex-1">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by name, brand, or SKU…') }}"
                        icon="magnifying-glass"
                        :label="false"
                        autocomplete="off"
                    />
                </div>

                <div class="w-40">
                    <select
                        wire:model.live="category_id"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">{{ __('All categories') }}</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status pills --}}
                <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-800">
                    @foreach(['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $val => $label)
                        <button
                            type="button"
                            wire:click="$set('status_filter', '{{ $val }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-xs font-medium transition',
                                'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => $status_filter === $val,
                                'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $status_filter !== $val,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>

                {{-- Stock pills --}}
                <div class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-700 dark:bg-zinc-800">
                    @foreach(['all' => 'All stock', 'low_stock' => 'Low', 'out_of_stock' => 'Out'] as $val => $label)
                        <button
                            type="button"
                            wire:click="filterByStock('{{ $val }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-xs font-medium transition',
                                'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => $stock_filter === $val,
                                'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $stock_filter !== $val,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Product table --}}
            <div
                class="flex-1 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                wire:loading.class="pointer-events-none opacity-60"
            >
                @if($this->products->isEmpty())
                    <div class="py-20 text-center">
                        @if($search || $category_id || $status_filter !== 'active' || $stock_filter !== 'all')
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No products match your search. Try adjusting your filters.') }}</p>
                        @else
                            <div class="mx-auto flex max-w-sm flex-col items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <svg class="h-8 w-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ __('No products yet.') }}</p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Add your first product to get started.') }}</p>
                                </div>
                                @if(auth()->user()?->isAdmin())
                                    <flux:button wire:click="openAdd" variant="primary" icon="plus">{{ __('Add product') }}</flux:button>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <table class="w-full min-w-[48rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-400">
                                <th class="w-14 pl-3 py-3"></th>
                                <th class="px-4 py-3">{{ __('Product') }}</th>
                                <th class="px-4 py-3">{{ __('Category') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Price') }}</th>
                                <th class="px-4 py-3">{{ __('Stock') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3">{{ __('AR') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($this->products as $product)
                                @php
                                    $stockStatus = $this->productStockStatus($product);
                                    $totalStock  = $this->productTotalStock($product);
                                    $isSelected  = $selectedProductId === $product->id;
                                    $thumbUrl    = $product->defaultVariant?->firstGalleryImage()?->image_url;
                                    $catSlug     = strtolower($product->category?->slug ?? '');
                                    $iconType    = match(true) {
                                        str_contains($catSlug, 'frame') || str_contains($catSlug, 'sunglass') => 'glasses',
                                        str_contains($catSlug, 'contact')                                     => 'eye',
                                        str_contains($catSlug, 'lens') || str_contains($catSlug, 'prescript') => 'lens',
                                        default                                                                => 'tag',
                                    };
                                @endphp
                                <tr
                                    wire:key="prod-{{ $product->id }}"
                                    wire:click="selectProduct({{ $product->id }})"
                                    @class([
                                        'cursor-pointer transition-colors',
                                        'border-l-2 border-sky-500 bg-sky-50/70 dark:bg-sky-950/20' => $isSelected,
                                        'bg-white hover:bg-zinc-50/80 dark:bg-zinc-900 dark:hover:bg-zinc-800/50' => !$isSelected,
                                    ])
                                >
                                    {{-- Thumbnail --}}
                                    <td class="pl-3 py-2.5 align-middle">
                                        @if($thumbUrl)
                                            <img src="{{ $thumbUrl }}" loading="lazy" alt=""
                                                 class="h-10 w-10 shrink-0 rounded-lg object-cover">
                                        @else
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                                @if($iconType === 'glasses')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <circle cx="7" cy="12" r="4"/><circle cx="17" cy="12" r="4"/>
                                                        <path stroke-linecap="round" d="M11 12h2M3 10.5 1.5 9.5M21 10.5l1.5-1"/>
                                                    </svg>
                                                @elseif($iconType === 'eye')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                    </svg>
                                                @elseif($iconType === 'lens')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                                                    </svg>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $product->name }}</div>
                                        @if($product->brand || ($product->variants_count ?? 0) > 1)
                                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                                @if($product->brand)
                                                    <span>{{ $product->brand }}</span>
                                                @endif
                                                @if(($product->variants_count ?? 0) > 1)
                                                    @if($product->brand)
                                                        <span class="text-zinc-400 dark:text-zinc-500" aria-hidden="true">·</span>
                                                    @endif
                                                    <span class="tabular-nums">{{ $product->variants_count }} {{ __('variants') }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        @if($product->category)
                                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                                {{ $product->category->name }}
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right align-middle font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                        ₱{{ number_format((float) $product->price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            {{-- Mini stock bar --}}
                                            @php
                                                $maxRef = max(1, $product->variants->max(fn($v) => ($v->inventory?->reorder_level ?? 5) * 3));
                                                $pct = min(100, (int) round($totalStock / $maxRef * 100));
                                                $barColor = match($stockStatus) {
                                                    'out'   => 'bg-red-500',
                                                    'low'   => 'bg-amber-400',
                                                    default => 'bg-emerald-500',
                                                };
                                            @endphp
                                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span @class([
                                                'text-xs font-medium tabular-nums',
                                                'text-red-600 dark:text-red-400'   => $stockStatus === 'out',
                                                'text-amber-600 dark:text-amber-400' => $stockStatus === 'low',
                                                'text-zinc-700 dark:text-zinc-300' => $stockStatus === 'healthy',
                                            ])>{{ $totalStock }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        @if($product->is_active)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-middle">
                                        @if($product->category?->has_ar_support && $product->variants->contains(fn ($v) => filled($v->ar_model_url)))
                                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-medium text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">AR</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ── RIGHT: Detail panel (desktop) ────────────────────────────── --}}
        @if($selectedProductId)
            <div class="hidden w-[380px] shrink-0 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:flex md:flex-col">
                <livewire:admin.products.variant-stock-panel
                    :product-id="$selectedProductId"
                    :key="'vsp-'.$selectedProductId"
                />
            </div>
        @endif

    </div>{{-- end main content --}}

    {{-- Nested slide-over + toasts must stay inside this single root (Livewire 3) --}}
    <livewire:admin.products.product-form key="product-form" />

    <div id="toast-container-pm" aria-live="polite" aria-atomic="true"
         class="pointer-events-none fixed bottom-5 right-5 z-[70] flex flex-col items-end gap-2"></div>

</div>{{-- end root --}}

@script
<script>
    function showToastPM(message, type) {
        const c = document.getElementById('toast-container-pm');
        if (!c) return;
        const bg = type === 'error' ? '#dc2626' : (type === 'info' ? '#0284c7' : '#059669');
        const t = document.createElement('div');
        t.style.cssText = `background:${bg};color:#fff;display:flex;align-items:center;gap:10px;
            border-radius:12px;padding:10px 16px;font-size:14px;font-weight:500;
            box-shadow:0 4px 12px rgba(0,0,0,.15);transform:translateY(8px);opacity:0;
            transition:opacity .25s ease,transform .25s ease;pointer-events:auto;max-width:360px;`;
        const icon = type === 'success'
            ? `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>`
            : `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="flex-shrink:0"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>`;
        t.innerHTML = icon + `<span>${message}</span>`;
        c.appendChild(t);
        requestAnimationFrame(() => requestAnimationFrame(() => {
            t.style.opacity = '1'; t.style.transform = 'translateY(0)';
        }));
        setTimeout(() => {
            t.style.opacity = '0'; t.style.transform = 'translateY(8px)';
            t.addEventListener('transitionend', () => t.remove(), { once: true });
        }, 4000);
    }

    $wire.on('toast', ({ message, type }) => showToastPM(message, type ?? 'success'));
</script>
@endscript
