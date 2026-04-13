<div class="flex flex-col gap-6">
    <div
        class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
    >
        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="min-w-0 flex-1">
                <label for="search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Search') }}
                </label>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    id="search"
                    :label="false"
                    placeholder="{{ __('Search by name or brand') }}"
                    autocomplete="off"
                />
            </div>

            <div class="w-full md:w-48">
                <label for="category_id" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Category') }}
                </label>
                <select
                    wire:model.live="category_id"
                    id="category_id"
                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                >
                    <option value="">{{ __('All categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-44">
                <label for="sort_by" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Sort by') }}
                </label>
                <select
                    wire:model.live="sort_by"
                    id="sort_by"
                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                >
                    <option value="created_at">{{ __('Newest') }}</option>
                    <option value="name">{{ __('Name') }}</option>
                    <option value="price">{{ __('Price') }}</option>
                    <option value="brand">{{ __('Brand') }}</option>
                    <option value="updated_at">{{ __('Last updated') }}</option>
                </select>
            </div>

            @if(auth()->user()?->isAdmin())
                <div class="w-full md:w-44">
                    <label for="status_filter" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Status') }}
                    </label>
                    <select
                        wire:model.live="status_filter"
                        id="status_filter"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="all">{{ __('All') }}</option>
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div
        class="flex-1 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        wire:loading.class="pointer-events-none opacity-60"
    >
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
            </flux:text>
            <div
                class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-600 dark:bg-zinc-800"
                role="group"
                aria-label="{{ __('Product list layout') }}"
            >
                <button
                    type="button"
                    wire:click="setListView('grid')"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => $listView === 'grid',
                        'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $listView !== 'grid',
                    ])
                >
                    {{ __('Grid') }}
                </button>
                <button
                    type="button"
                    wire:click="setListView('table')"
                    @class([
                        'rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => $listView === 'table',
                        'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $listView !== 'table',
                    ])
                >
                    {{ __('Table') }}
                </button>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('No products found. Try adjusting your filters.') }}
            </div>
        @elseif($listView === 'table')
            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-400"
                        >
                            <th class="px-4 py-3.5">{{ __('Image') }}</th>
                            <th class="px-4 py-3.5">{{ __('Product') }}</th>
                            <th class="px-4 py-3.5">{{ __('Category') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('Price') }}</th>
                            @if(auth()->user()?->isAdminOrStaff())
                                <th class="px-4 py-3.5 text-end">{{ __('Stock') }}</th>
                            @endif
                            <th class="px-4 py-3.5">{{ __('Rating') }}</th>
                            <th class="px-4 py-3.5">{{ __('Status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($products as $product)
                            @php
                                $catalogView = ! auth()->user()?->isAdminOrStaff();
                                $variantsForCatalog = $catalogView
                                    ? $product->variants->filter(fn ($v) => $v->is_active ?? true)
                                    : $product->variants;
                                $thumbVariant = $catalogView
                                    ? ($variantsForCatalog->sortBy('id')->first() ?? $product->defaultVariant)
                                    : $product->defaultVariant;
                                $thumbUrl = $thumbVariant?->firstGalleryImage()?->image_url
                                    ?? $product->images->first()?->image_url;
                                $totalStock = (int) $variantsForCatalog->sum(fn ($v) => $v->inventory?->quantity ?? 0);
                                $variantCountTable = $variantsForCatalog->count();
                                $hasLowVariant = $variantsForCatalog->contains(function ($v) {
                                    $inv = $v->inventory;
                                    $q = (int) ($inv?->quantity ?? 0);
                                    $rl = (int) ($inv?->reorder_level ?? 5);

                                    return $q > 0 && $q <= $rl;
                                });
                                $stockIsOut = $totalStock === 0;
                                $stockIsLow = ! $stockIsOut && $hasLowVariant;
                                $stockIsHealthy = ! $stockIsOut && ! $stockIsLow;
                                $reorderMax = $variantCountTable > 0
                                    ? max(1, $variantsForCatalog->map(fn ($v) => (int) ($v->inventory?->reorder_level ?? 5))->max())
                                    : 5;
                                $stockBarMaxRef = max(50, $reorderMax * 3);
                                $stockBarPct = $stockIsOut
                                    ? 8
                                    : min(100, (int) round(($totalStock / $stockBarMaxRef) * 100));
                                $rowPrice = $catalogView
                                    ? ($variantsForCatalog->sortBy('id')->first()?->price ?? 0)
                                    : ($product->price ?? 0);
                            @endphp
                            <tr
                                wire:key="table-product-{{ $product->id }}"
                                class="bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/60"
                            >
                                <td class="px-4 py-3 align-middle">
                                    <div
                                        class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800"
                                    >
                                        @if($thumbUrl)
                                            <img
                                                src="{{ $thumbUrl }}"
                                                alt=""
                                                class="h-full w-full object-cover"
                                            >
                                        @else
                                            <x-product-image-placeholder class="h-full w-full" />
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="max-w-md">
                                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                            <span class="font-semibold leading-snug text-zinc-900 dark:text-zinc-50">
                                                {{ $product->name }}
                                            </span>
                                            @if($variantCountTable > 1)
                                                <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium tabular-nums text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                                    {{ trans_choice('{1} :count variant|[2,*] :count variants', $variantCountTable, ['count' => $variantCountTable]) }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $product->brand ?? '—' }}
                                        </p>
                                        @if($variantsForCatalog->contains(fn ($v) => filled($v->ar_model_url)))
                                            <span class="mt-1 inline-block text-[11px] font-medium text-sky-600 dark:text-sky-400">
                                                {{ __('AR') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-middle text-zinc-700 dark:text-zinc-300">
                                    {{ $product->category?->name ?? __('Uncategorized') }}
                                </td>
                                <td
                                    class="px-4 py-3 align-middle text-end text-base font-bold tabular-nums text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ \App\Support\Money::peso($rowPrice) }}
                                </td>
                                @if(auth()->user()?->isAdminOrStaff())
                                    <td class="px-4 py-3 align-middle">
                                        <div class="flex items-center justify-end gap-2.5">
                                            <div class="h-2 w-14 shrink-0 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                @if($stockIsOut)
                                                    <div class="h-full w-[12%] rounded-full bg-red-500 dark:bg-red-500"></div>
                                                @elseif($stockIsLow)
                                                    <div
                                                        class="h-full rounded-full bg-amber-400 dark:bg-amber-500"
                                                        style="width: {{ max(18, $stockBarPct) }}%"
                                                    ></div>
                                                @else
                                                    <div class="h-full w-full rounded-full bg-emerald-600 dark:bg-emerald-500"></div>
                                                @endif
                                            </div>
                                            <span
                                                @class([
                                                    'text-sm font-semibold tabular-nums shrink-0 min-w-[2rem] text-end',
                                                    'text-red-600 dark:text-red-400' => $stockIsOut,
                                                    'text-amber-600 dark:text-amber-400' => $stockIsLow,
                                                    'text-zinc-800 dark:text-zinc-200' => $stockIsHealthy,
                                                ])
                                            >{{ number_format($totalStock) }}</span>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-4 py-3 align-middle">
                                    @if(($product->reviews_count ?? 0) > 0)
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                            <span class="text-xs tabular-nums text-zinc-700 dark:text-zinc-300">
                                                {{ number_format($product->average_rating ?? 0, 1) }}
                                            </span>
                                            <span class="text-[11px] text-zinc-400 dark:text-zinc-500">({{ $product->reviews_count }})</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    @if($product->is_active ?? true)
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200"
                                        >
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200"
                                        >
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            :href="route('products.show', $product)"
                                            wire:navigate
                                        >
                                            {{ __('View') }}
                                        </flux:button>
                                        @if(auth()->user()?->isAdmin())
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                :href="route('products.edit', $product)"
                                                wire:navigate
                                            >
                                                {{ __('Edit') }}
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                {{ $products->links() }}
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($products as $product)
                    @php
                        $catalogView = ! auth()->user()?->isAdminOrStaff();
                        $variantsForCatalog = $catalogView
                            ? $product->variants->filter(fn ($v) => $v->is_active ?? true)
                            : $product->variants;
                        $thumbVariant = $catalogView
                            ? ($variantsForCatalog->sortBy('id')->first() ?? $product->defaultVariant)
                            : $product->defaultVariant;
                        $thumbUrl = $thumbVariant?->firstGalleryImage()?->image_url
                            ?? $product->images->first()?->image_url;
                        $variantCount = $variantsForCatalog->count();
                        $gridPrice = $catalogView
                            ? ($variantsForCatalog->sortBy('id')->first()?->price ?? 0)
                            : ($product->price ?? 0);
                        $showArBadge = ($product->category?->has_ar_support ?? false)
                            && $variantsForCatalog->contains(fn ($v) => filled($v->ar_model_url));
                        $reviewCount = (int) ($product->reviews_count ?? 0);
                        $totalStockGrid = (int) $variantsForCatalog->sum(fn ($v) => $v->inventory?->quantity ?? 0);
                        $hasLowVariantGrid = $variantsForCatalog->contains(function ($v) {
                            $inv = $v->inventory;
                            $q = (int) ($inv?->quantity ?? 0);
                            $rl = (int) ($inv?->reorder_level ?? 5);

                            return $q > 0 && $q <= $rl;
                        });
                        $stockIsOutGrid = $totalStockGrid === 0;
                        $stockIsLowGrid = ! $stockIsOutGrid && $hasLowVariantGrid;
                        $stockIsHealthyGrid = ! $stockIsOutGrid && ! $stockIsLowGrid;
                        $reorderMaxGrid = $variantCount > 0
                            ? max(1, $variantsForCatalog->map(fn ($v) => (int) ($v->inventory?->reorder_level ?? 5))->max())
                            : 5;
                        $stockBarMaxRefGrid = max(50, $reorderMaxGrid * 3);
                        $stockBarPctGrid = $stockIsOutGrid
                            ? 8
                            : min(100, (int) round(($totalStockGrid / $stockBarMaxRefGrid) * 100));
                    @endphp
                    <a
                        wire:key="grid-product-{{ $product->id }}"
                        href="{{ route('products.show', $product) }}"
                        class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:shadow-lg dark:hover:shadow-zinc-950/50"
                        wire:navigate
                    >
                        {{-- Top: media (status + AR badges top-right, image centered) --}}
                        <div class="relative aspect-[4/3] w-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="absolute right-2 top-2 z-10 flex max-w-[calc(100%-1rem)] flex-wrap items-center justify-end gap-1.5">
                                @if($product->is_active ?? true)
                                    <span
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200"
                                    >
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span
                                        class="rounded-full bg-zinc-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
                                    >
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                                @if($showArBadge)
                                    <span
                                        class="rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-purple-800 dark:bg-purple-950/70 dark:text-purple-200"
                                    >
                                        {{ __('AR') }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex h-full w-full items-center justify-center p-4">
                                @if($thumbUrl)
                                    <img
                                        src="{{ $thumbUrl }}"
                                        alt=""
                                        class="max-h-full max-w-full object-contain"
                                    >
                                @else
                                    <x-product-image-placeholder :caption="false" class="h-28 w-28 shrink-0 rounded-lg opacity-90" />
                                @endif
                            </div>
                        </div>

                        {{-- Bottom: category → name → brand → [price | variants] --}}
                        <div class="flex flex-1 flex-col p-4 pt-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ $product->category?->name ?? __('Uncategorized') }}
                            </p>
                            <p class="mt-1 line-clamp-2 text-base font-semibold leading-snug text-zinc-900 group-hover:text-sky-700 dark:text-zinc-50 dark:group-hover:text-sky-400">
                                {{ $product->name }}
                            </p>
                            <p class="mt-0.5 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $product->brand ?? '—' }}
                            </p>

                            <div class="mt-1.5 flex min-h-[1.25rem] items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                                @if($reviewCount > 0)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span class="tabular-nums font-medium text-zinc-700 dark:text-zinc-300">{{ number_format($product->average_rating ?? 0, 1) }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-500">
                                        {{ trans_choice('{1} :count review|[2,*] :count reviews', $reviewCount, ['count' => $reviewCount]) }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">{{ __('No reviews yet') }}</span>
                                @endif
                            </div>

                            @if(auth()->user()?->isAdminOrStaff())
                                <div
                                    class="mt-2 flex items-center gap-2"
                                    title="{{ __('Total quantity across variants') }}"
                                >
                                    <div class="h-2 w-14 shrink-0 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        @if($stockIsOutGrid)
                                            <div class="h-full w-[12%] rounded-full bg-red-500 dark:bg-red-500"></div>
                                        @elseif($stockIsLowGrid)
                                            <div
                                                class="h-full rounded-full bg-amber-400 dark:bg-amber-500"
                                                style="width: {{ max(18, $stockBarPctGrid) }}%"
                                            ></div>
                                        @else
                                            <div class="h-full w-full rounded-full bg-emerald-600 dark:bg-emerald-500"></div>
                                        @endif
                                    </div>
                                    <span
                                        @class([
                                            'text-xs font-semibold tabular-nums',
                                            'text-red-600 dark:text-red-400' => $stockIsOutGrid,
                                            'text-amber-600 dark:text-amber-400' => $stockIsLowGrid,
                                            'text-zinc-800 dark:text-zinc-200' => $stockIsHealthyGrid,
                                        ])
                                    >{{ number_format($totalStockGrid) }}</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Stock') }}</span>
                                </div>
                            @endif

                            <div class="mt-auto flex items-end justify-between gap-3 pt-3">
                                <span class="text-base font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                                    {{ \App\Support\Money::peso($gridPrice) }}
                                </span>
                                <span class="flex shrink-0 items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                    <span class="tabular-nums">
                                        {{ trans_choice('{1} :count variant|[2,*] :count variants', $variantCount, ['count' => $variantCount]) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-4 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
