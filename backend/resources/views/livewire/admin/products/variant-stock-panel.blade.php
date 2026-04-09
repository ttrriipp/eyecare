@php
    $product = $this->product;
    $cat     = $product?->category;
@endphp

{{-- Single root element — wraps header + tabs + content + fixed overlays.
     The flex-col here drives the internal layout; fixed children (backdrop,
     variant slide-over) are taken out of flow and don't affect sizing. --}}
<div class="flex min-h-0 flex-1 flex-col">

@if(!$product)
    <div class="flex flex-1 items-center justify-center p-8">
        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('Product not found.') }}</p>
    </div>
@else

{{-- ── Panel header ─────────────────────────────────────────────────── --}}
<div class="flex shrink-0 items-start gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
    {{-- Product thumbnail --}}
    @php
        $thumbUrl = $product->defaultVariant?->primaryImage?->image_url;
        $catSlug  = strtolower($cat?->slug ?? '');
        $iconType = match(true) {
            str_contains($catSlug, 'frame') || str_contains($catSlug, 'sunglass') => 'glasses',
            str_contains($catSlug, 'contact')                                      => 'eye',
            str_contains($catSlug, 'lens') || str_contains($catSlug, 'prescript')  => 'lens',
            default                                                                  => 'tag',
        };
    @endphp
    @if($thumbUrl)
        <img src="{{ $thumbUrl }}" loading="lazy" alt=""
             class="h-16 w-16 shrink-0 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
    @else
        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">
            @if($iconType === 'glasses')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <circle cx="7" cy="12" r="4"/><circle cx="17" cy="12" r="4"/>
                    <path stroke-linecap="round" d="M11 12h2M3 10.5 1.5 9.5M21 10.5l1.5-1"/>
                </svg>
            @elseif($iconType === 'eye')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
            @elseif($iconType === 'lens')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                </svg>
            @endif
        </div>
    @endif

    {{-- Name + badges + buttons --}}
    <div class="flex min-w-0 flex-1 items-start justify-between gap-2">
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $product->name }}</p>
        <div class="mt-1 flex flex-wrap items-center gap-1.5">
            @if($cat)
                <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                    {{ $cat->name }}
                </span>
            @endif
            @if($cat?->has_ar_support)
                <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-medium text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">AR</span>
            @endif
            @if($cat?->requires_prescription)
                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">Rx</span>
            @endif
        </div>
    </div>
    <div class="flex shrink-0 items-center gap-1">
        @if(auth()->user()?->isAdmin())
            <button type="button" wire:click="requestEdit"
                class="flex h-8 w-8 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                title="{{ __('Edit product') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
            </button>
        @endif
        <button type="button" wire:click="requestClose"
            class="flex h-8 w-8 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
            title="{{ __('Close') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
        </button>
    </div>
    </div>{{-- end name+buttons wrapper --}}
</div>

{{-- ── Tab nav ──────────────────────────────────────────────────────── --}}
<div class="flex shrink-0 border-b border-zinc-200 dark:border-zinc-700">
    @foreach(['overview' => __('Overview & Variants'), 'stock' => __('Stock')] as $tab => $label)
        <button type="button" wire:click="setTab('{{ $tab }}')"
            @class([
                'flex-1 px-3 py-2.5 text-xs font-medium transition border-b-2',
                'border-sky-600 text-sky-600 dark:border-sky-400 dark:text-sky-400' => $activeTab === $tab,
                'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $activeTab !== $tab,
            ])
        >{{ $label }}</button>
    @endforeach
</div>

{{-- ── Scrollable content ───────────────────────────────────────────── --}}
<div class="flex-1 overflow-y-auto">

    {{-- ══════════════════════════════════════════════════════
         TAB 1: Overview & Variants
         ══════════════════════════════════════════════════════ --}}
    @if($activeTab === 'overview')
        <div class="p-4 space-y-4">

            {{-- Product overview --}}
            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('Brand') }}</p>
                    <p class="mt-0.5 text-zinc-800 dark:text-zinc-200">{{ $product->brand ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('SKU') }}</p>
                    <p class="mt-0.5 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $product->defaultVariant?->sku ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('Base price') }}</p>
                    <p class="mt-0.5 font-semibold text-emerald-600 dark:text-emerald-400">₱{{ number_format((float) $product->price, 2) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('Status') }}</p>
                    <p class="mt-0.5">
                        @if($product->is_active)
                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Active') }}</span>
                        @else
                            <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Inactive') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            @if($product->description)
                <p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-3">{{ $product->description }}</p>
            @endif

            @if($cat?->has_ar_support && $product->variants->contains(fn ($v) => filled($v->ar_model_url)))
                <div class="flex items-center gap-2 text-xs text-purple-700 dark:text-purple-300">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" /></svg>
                    {{ __('AR try-on enabled') }}
                </div>
            @endif

            @if($cat?->requires_prescription)
                <div class="flex items-center gap-2 text-xs text-emerald-700 dark:text-emerald-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    {{ __('Requires prescription at checkout') }}
                </div>
            @endif

            {{-- Divider --}}
            <div class="border-t border-zinc-100 dark:border-zinc-800"></div>

            {{-- Variants heading --}}
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Variants') }}
                </h3>
                @if(auth()->user()?->isAdmin())
                    <button type="button" wire:click="openAddVariant"
                        class="inline-flex items-center gap-1 text-xs font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Add variant') }}
                    </button>
                @endif
            </div>

            {{-- Variant cards --}}
            @forelse($product->variants as $variant)
                @php
                    $inv    = $variant->inventory;
                    $qty    = $inv?->quantity ?? 0;
                    $status = $this->stockStatusForVariant($variant);
                    $label  = $this->buildVariantLabel($variant);
                    $adj    = (float) $variant->price_adjustment;
                @endphp
                <div class="relative rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ $label }}</p>
                            <p class="mt-0.5 font-mono text-[11px] text-zinc-400 dark:text-zinc-500">{{ $variant->sku }}</p>
                            @if($adj > 0)
                                <p class="mt-0.5 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">+₱{{ number_format($adj, 2) }}</p>
                            @elseif($adj < 0)
                                <p class="mt-0.5 text-[11px] font-medium text-red-500 dark:text-red-400">₱{{ number_format($adj, 2) }}</p>
                            @else
                                <p class="mt-0.5 text-[11px] text-zinc-400">{{ __('Base price') }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <div class="flex items-center gap-1.5">
                                <div @class([
                                    'h-2 w-2 rounded-full',
                                    'bg-red-500'    => $status === 'out',
                                    'bg-amber-400'  => $status === 'low',
                                    'bg-emerald-500' => $status === 'healthy',
                                ])></div>
                                <span @class([
                                    'text-sm font-bold tabular-nums',
                                    'text-red-600 dark:text-red-400'     => $status === 'out',
                                    'text-amber-600 dark:text-amber-400' => $status === 'low',
                                    'text-zinc-700 dark:text-zinc-300'   => $status === 'healthy',
                                ])>{{ $qty }}</span>
                            </div>
                            @if(auth()->user()?->isAdmin())
                                <button type="button" wire:click="openEditVariant({{ $variant->id }})"
                                    class="mt-1 text-[11px] text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 underline">
                                    {{ __('Edit') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-xs text-zinc-400 dark:text-zinc-500">
                    {{ __('No variants yet. Add a variant to make this product available for ordering.') }}
                </p>
            @endforelse
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         TAB 2: Stock
         ══════════════════════════════════════════════════════ --}}
    @if($activeTab === 'stock')
        <div class="p-4 space-y-4">

            {{-- Stock summary --}}
            @php
                $allInventories  = $product->variants->pluck('inventory')->filter();
                $totalStockTab   = $allInventories->sum('quantity');
                $unit            = $cat?->stock_unit ?? 'units';
                $anyOut          = $allInventories->contains(fn($i) => $i->quantity === 0);
                $anyLow          = !$anyOut && $allInventories->contains(fn($i) => $i->quantity <= $i->reorder_level);
                $overallStatus   = $anyOut ? 'out' : ($anyLow ? 'low' : 'healthy');
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white p-4 text-center dark:border-zinc-700 dark:bg-zinc-800/40">
                <p class="text-4xl font-bold tabular-nums
                    {{ $overallStatus === 'out' ? 'text-red-600 dark:text-red-400' : ($overallStatus === 'low' ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-50') }}">
                    {{ number_format($totalStockTab) }}
                </p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $unit }}</p>
                <span @class([
                    'mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                    'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300'         => $overallStatus === 'out',
                    'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' => $overallStatus === 'low',
                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $overallStatus === 'healthy',
                ])>
                    {{ $overallStatus === 'out' ? __('Out of stock') : ($overallStatus === 'low' ? __('Low stock') : __('In stock')) }}
                </span>
            </div>

            {{-- Inline confirmation message --}}
            @if($adjConfirmation)
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                    ✓ {{ $adjConfirmation }}
                </div>
            @endif

            {{-- Per-variant stock list --}}
            @foreach($product->variants as $variant)
                @php
                    $inv       = $variant->inventory;
                    $qty       = $inv?->quantity ?? 0;
                    $threshold = $inv?->reorder_level ?? 5;
                    $maxRef    = max(1, $threshold * 3);
                    $pct       = min(100, (int) round($qty / $maxRef * 100));
                    $status    = $this->stockStatusForVariant($variant);
                    $label     = $this->buildVariantLabel($variant);
                    $isAdj     = $adjustingVariantId === $variant->id;
                @endphp
                <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/40">
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</p>
                                {{-- Stock bar --}}
                                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                    <div @class([
                                        'h-full rounded-full transition-all',
                                        'bg-red-500'     => $status === 'out',
                                        'bg-amber-400'   => $status === 'low',
                                        'bg-emerald-500' => $status === 'healthy',
                                    ]) style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <span @class([
                                    'text-sm font-bold tabular-nums',
                                    'text-red-600 dark:text-red-400'     => $status === 'out',
                                    'text-amber-600 dark:text-amber-400' => $status === 'low',
                                    'text-zinc-800 dark:text-zinc-200'   => $status === 'healthy',
                                ])>{{ $qty }}</span>
                                <span class="ml-1 text-xs text-zinc-400">{{ $unit }}</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <span @class([
                                'text-[11px] font-medium',
                                'text-red-600 dark:text-red-400'     => $status === 'out',
                                'text-amber-600 dark:text-amber-400' => $status === 'low',
                                'text-emerald-600 dark:text-emerald-400' => $status === 'healthy',
                            ])>{{ $status === 'out' ? __('Out of stock') : ($status === 'low' ? __('Low stock') : __('In stock')) }}</span>
                            @if(auth()->user()?->isAdmin())
                                <button type="button" wire:click="startAdjust({{ $variant->id }})"
                                    class="text-[11px] font-medium text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">
                                    {{ $isAdj ? __('Cancel') : __('Adjust') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Inline adjustment form --}}
                    @if($isAdj)
                        <div class="border-t border-zinc-100 bg-zinc-50 p-4 space-y-3 dark:border-zinc-700 dark:bg-zinc-800/60">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-[11px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Type') }}</label>
                                    <select wire:model.live="adj_type"
                                        class="block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                        <option value="add">{{ __('Add stock') }}</option>
                                        <option value="remove">{{ __('Remove stock') }}</option>
                                        <option value="set">{{ __('Set exact qty') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] font-medium text-zinc-600 dark:text-zinc-400">
                                        {{ __('Quantity') }} ({{ $unit }})
                                    </label>
                                    <input wire:model="adj_quantity" type="number" min="{{ $adj_type === 'set' ? 0 : 1 }}"
                                        class="block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                    >
                                    @error('adj_quantity') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-[11px] font-medium text-zinc-600 dark:text-zinc-400">{{ __('Reason') }}</label>
                                <select wire:model.live="adj_reason"
                                    class="block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="restock">{{ __('Restock') }}</option>
                                    <option value="sale_correction">{{ __('Sale correction') }}</option>
                                    <option value="damaged">{{ __('Damaged') }}</option>
                                    <option value="expired">{{ __('Expired') }}</option>
                                    <option value="returned">{{ __('Returned') }}</option>
                                    <option value="initial_count">{{ __('Initial count') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>

                            @if($adj_reason === 'other')
                                <div>
                                    <label class="mb-1 block text-[11px] font-medium text-zinc-600 dark:text-zinc-400">
                                        {{ __('Notes') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model="adj_notes" type="text" maxlength="300"
                                        placeholder="{{ __('Describe the reason…') }}"
                                        class="block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                    >
                                    @error('adj_notes') <p class="mt-0.5 text-[11px] text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div class="flex items-center justify-between gap-2">
                                <button type="button" wire:click="cancelAdjust"
                                    class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="button" wire:click="saveAdjustment"
                                    wire:loading.attr="disabled" wire:target="saveAdjustment"
                                    class="inline-flex h-7 items-center gap-1.5 rounded-md bg-sky-600 px-3 text-xs font-medium text-white hover:bg-sky-700 disabled:opacity-60">
                                    <span wire:loading.remove wire:target="saveAdjustment">{{ __('Save adjustment') }}</span>
                                    <span wire:loading wire:target="saveAdjustment" class="flex items-center gap-1.5">
                                        <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        {{ __('Saving…') }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Recent adjustment history --}}
            @if($this->recentAdjustments->isNotEmpty())
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                        {{ __('Recent adjustments') }}
                    </h4>
                    <div class="space-y-1.5">
                        @foreach($this->recentAdjustments as $adj)
                            @php
                                $adjVariant = $adj->inventory?->productVariant;
                                $adjLabel   = $adjVariant ? $this->buildVariantLabel($adjVariant) : '—';
                            @endphp
                            <div class="flex items-center justify-between text-xs">
                                <div class="min-w-0 flex-1 truncate text-zinc-500 dark:text-zinc-400">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $adjLabel }}</span>
                                    · {{ ucfirst(str_replace('_', ' ', explode(':', $adj->reason)[0])) }}
                                    · {{ $adj->adjustedBy?->name ?? __('System') }}
                                </div>
                                <div class="ml-2 shrink-0 font-bold tabular-nums
                                    {{ $adj->delta >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $adj->delta >= 0 ? '+' : '' }}{{ $adj->delta }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('admin.inventory.adjustments', ['product' => $productId]) }}"
                        class="mt-3 block text-xs text-sky-600 hover:underline dark:text-sky-400"
                        wire:navigate>
                        {{ __('View full adjustment history →') }}
                    </a>
                </div>
            @endif

        </div>
    @endif

</div>{{-- end scrollable content --}}

{{-- ══════════════════════════════════════════════════════════════════════
     VARIANT SLIDE-OVER (add / edit variant)
     ══════════════════════════════════════════════════════════════════════ --}}

{{-- Backdrop --}}
<div wire:click="closeVariantForm" aria-hidden="true"
     class="fixed inset-0 z-50 bg-black/40 transition-opacity duration-200
            {{ $showVariantForm ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"></div>

{{-- Slide-over --}}
<div role="dialog" aria-modal="true"
     class="fixed inset-y-0 right-0 z-[60] flex w-full max-w-sm flex-col bg-white shadow-2xl transition-transform duration-200
            dark:bg-zinc-900 sm:border-l sm:border-zinc-200 dark:sm:border-zinc-700
            {{ $showVariantForm ? 'translate-x-0' : 'translate-x-full' }}"
>
    {{-- Header --}}
    <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">
            {{ $variantFormMode === 'add' ? __('Add variant') : __('Edit variant') }}
        </h2>
        <button type="button" wire:click="closeVariantForm"
            class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
        </button>
    </div>

    {{-- Body --}}
    <div class="flex-1 overflow-y-auto p-4">

        @if(!$showVariantDeleteConfirm)
            <div class="space-y-3">

                @if($cat?->has_color)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Color / finish') }} <span class="text-red-500">*</span></label>
                        <input wire:model="v_color" type="text" maxlength="60"
                            placeholder="{{ __('e.g. Gold, Matte Black') }}"
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                        >
                        @error('v_color') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($cat?->has_frame_size)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Frame size') }} <span class="text-red-500">*</span></label>
                        <select wire:model="v_frame_size"
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            <option value="">{{ __('Select…') }}</option>
                            <option value="Small (50mm)">Small (50mm)</option>
                            <option value="Medium (54mm)">Medium (54mm)</option>
                            <option value="Large (56mm)">Large (56mm)</option>
                            <option value="XL (58mm)">XL (58mm)</option>
                        </select>
                        @error('v_frame_size') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($cat?->has_material)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Material') }} <span class="text-red-500">*</span></label>
                        <select wire:model="v_material"
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            <option value="">{{ __('Select…') }}</option>
                            <option value="Acetate">Acetate</option>
                            <option value="Titanium">Titanium</option>
                            <option value="Metal">Metal</option>
                            <option value="TR-90">TR-90</option>
                            <option value="Polycarbonate">Polycarbonate</option>
                            <option value="High-index">High-index</option>
                            <option value="CR-39 Plastic">CR-39 Plastic</option>
                            <option value="Stainless Steel">Stainless Steel</option>
                        </select>
                        @error('v_material') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($cat?->has_lens_type)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Lens type') }} <span class="text-red-500">*</span></label>
                        <select wire:model="v_lens_type"
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            <option value="">{{ __('Select…') }}</option>
                            @if(str_contains(strtolower($cat->name ?? ''), 'sunglass'))
                                <option value="Classic tint">Classic tint</option>
                                <option value="Polarized">Polarized</option>
                                <option value="Mirrored">Mirrored</option>
                                <option value="Gradient">Gradient</option>
                            @elseif(str_contains(strtolower($cat->name ?? ''), 'contact'))
                                <option value="Daily">Daily</option>
                                <option value="Bi-weekly">Bi-weekly</option>
                                <option value="Monthly">Monthly</option>
                                <option value="Quarterly">Quarterly</option>
                            @elseif(str_contains(strtolower($cat->name ?? ''), 'prescription lens'))
                                <option value="Single Vision">Single Vision</option>
                                <option value="Bifocal">Bifocal</option>
                                <option value="Progressive">Progressive</option>
                                <option value="Reading">Reading</option>
                            @else
                                <option value="Clear">Clear</option>
                                <option value="Blue light filter">Blue light filter</option>
                                <option value="Photochromic">Photochromic</option>
                                <option value="Anti-radiation">Anti-radiation</option>
                            @endif
                        </select>
                        @error('v_lens_type') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($cat?->has_power_field)
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Base curve (mm)') }} <span class="text-red-500">*</span></label>
                            <input wire:model="v_base_curve" type="number" step="0.1" placeholder="8.5"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                            >
                            @error('v_base_curve') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Diameter (mm)') }} <span class="text-red-500">*</span></label>
                            <input wire:model="v_diameter" type="number" step="0.1" placeholder="14.2"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                            >
                            @error('v_diameter') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Price adjustment') }} (₱)</label>
                    <input wire:model="v_price_adjustment" type="number" step="0.01"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                    <p class="mt-0.5 text-[11px] text-zinc-400">{{ __('Added to base price. Use 0 if same.') }}</p>
                </div>

                @if($cat?->has_ar_support)
                    <div>
                        <label class="mb-1 flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('AR model URL') }}
                            <span class="inline-flex rounded-full bg-purple-100 px-1.5 py-0.5 text-[10px] font-medium text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">AR</span>
                            <span class="font-normal text-zinc-400">({{ __('optional') }})</span>
                        </label>
                        <input wire:model.blur="v_ar_model_url" type="url"
                            placeholder="https://…/model.glb"
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                        >
                        @error('v_ar_model_url') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

        @else
            {{-- Delete confirmation --}}
            <div class="flex flex-col items-center gap-3 py-4 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-950/50">
                    <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ __('Delete this variant?') }}</h3>
                    @if($variantDeleteStockQty > 0)
                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                            {{ __('This variant has') }} <strong>{{ $variantDeleteStockQty }}</strong> {{ $cat?->stock_unit ?? 'units' }} {{ __('in stock. Deleting it will also remove its inventory record.') }}
                        </p>
                    @else
                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ __('This variant has no stock. This action cannot be undone.') }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="shrink-0 border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
        @if(!$showVariantDeleteConfirm)
            <div class="flex items-center justify-between gap-2">
                @if($variantFormMode === 'edit')
                    <button type="button" wire:click="confirmDeleteVariant"
                        class="inline-flex h-8 items-center rounded-md border border-red-300 px-3 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-950/30">
                        {{ __('Delete variant') }}
                    </button>
                @else
                    <div></div>
                @endif
                <div class="flex gap-2">
                    <button type="button" wire:click="closeVariantForm"
                        class="inline-flex h-8 items-center rounded-md border border-zinc-300 bg-white px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="saveVariant"
                        wire:loading.attr="disabled" wire:target="saveVariant"
                        class="inline-flex h-8 items-center gap-1.5 rounded-md bg-sky-600 px-3 text-xs font-medium text-white hover:bg-sky-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveVariant">
                            {{ $variantFormMode === 'add' ? __('Add variant') : __('Save changes') }}
                        </span>
                        <span wire:loading wire:target="saveVariant">
                            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                    </button>
                </div>
            </div>
        @else
            <div class="flex items-center justify-end gap-2">
                <button type="button" wire:click="$set('showVariantDeleteConfirm', false)"
                    class="inline-flex h-8 items-center rounded-md border border-zinc-300 bg-white px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="deleteVariant"
                    wire:loading.attr="disabled" wire:target="deleteVariant"
                    class="inline-flex h-8 items-center rounded-md bg-red-600 px-3 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-60">
                    {{ __('Yes, delete variant') }}
                </button>
            </div>
        @endif
    </div>
</div>{{-- end variant slide-over --}}

@endif{{-- end @if($product) --}}

</div>{{-- end single root --}}
