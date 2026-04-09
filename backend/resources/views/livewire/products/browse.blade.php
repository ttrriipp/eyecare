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
                    placeholder="{{ __('Search by name, brand, or SKU') }}"
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

            <div class="w-full md:w-32">
                <label for="sort_dir" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Order') }}
                </label>
                <select
                    wire:model.live="sort_dir"
                    id="sort_dir"
                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                >
                    <option value="desc">{{ __('Desc') }}</option>
                    <option value="asc">{{ __('Asc') }}</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-4 md:ml-auto">
                @if(auth()->user()?->isAdmin())
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input
                            wire:model.live="include_inactive"
                            type="checkbox"
                            class="h-4 w-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950"
                        >
                        <span>{{ __('Include inactive') }}</span>
                    </label>
                @endif

                <div class="ml-auto flex gap-2">
                    <flux:button type="button" wire:click="resetFilters" variant="ghost" wire:loading.attr="disabled">
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            </div>
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
                <table class="w-full min-w-[60rem] text-left text-sm">
                    <thead>
                        <tr
                            class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-400"
                        >
                            <th class="px-4 py-3.5">{{ __('Image') }}</th>
                            <th class="px-4 py-3.5">{{ __('Product') }}</th>
                            <th class="px-4 py-3.5">{{ __('SKU') }}</th>
                            <th class="px-4 py-3.5">{{ __('Brand') }}</th>
                            <th class="px-4 py-3.5">{{ __('Category') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('Price') }}</th>
                            <th class="px-4 py-3.5">{{ __('Rating') }}</th>
                            <th class="px-4 py-3.5">{{ __('Status') }}</th>
                            <th class="px-4 py-3.5 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($products as $product)
                            @php
                                $thumbUrl = $product->images->first()?->image_url;
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
                                    <div class="max-w-xs font-semibold text-zinc-900 dark:text-zinc-50">
                                        {{ $product->name }}
                                    </div>
                                    @if($product->variants->contains(fn ($v) => filled($v->ar_model_url)))
                                        <span class="mt-0.5 inline-block text-[11px] font-medium text-sky-600 dark:text-sky-400">
                                            {{ __('AR') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-middle font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $product->sku ?? '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle text-zinc-700 dark:text-zinc-300">
                                    {{ $product->brand ?? '—' }}
                                </td>
                                <td class="px-4 py-3 align-middle text-zinc-700 dark:text-zinc-300">
                                    {{ $product->category?->name ?? __('Uncategorized') }}
                                </td>
                                <td
                                    class="px-4 py-3 align-middle text-end text-base font-bold tabular-nums text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ \App\Support\Money::peso($product->price ?? 0) }}
                                </td>
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
                    <a
                        wire:key="grid-product-{{ $product->id }}"
                        href="{{ route('products.show', $product) }}"
                        class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:shadow-lg dark:hover:shadow-zinc-950/50"
                        wire:navigate
                    >
                        @php
                            $imageUrl = $product->images->first()?->image_url;
                        @endphp
                        <div class="aspect-[4/3] w-full bg-zinc-100 transition group-hover:opacity-95 dark:bg-zinc-800">
                            @if($imageUrl)
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <x-product-image-placeholder class="h-full w-full" />
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <div
                                    class="line-clamp-2 text-base font-bold leading-snug text-zinc-900 group-hover:text-sky-700 dark:text-zinc-50 dark:group-hover:text-sky-400"
                                >
                                    {{ $product->name }}
                                </div>
                                <div
                                    class="text-lg font-bold tabular-nums text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ \App\Support\Money::peso($product->price ?? 0) }}
                                </div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $product->brand ?? '—' }}
                                </div>
                                @if($product->sku)
                                    <div class="text-[11px] leading-tight text-zinc-400 dark:text-zinc-500">
                                        <span class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('SKU') }}:</span>
                                        {{ $product->sku }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xs text-zinc-500 dark:text-zinc-500">
                                    {{ $product->category?->name ?? __('Uncategorized') }}
                                </div>
                                @if(($product->reviews_count ?? 0) > 0)
                                    <div class="flex shrink-0 items-center gap-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3 text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        {{ number_format($product->average_rating ?? 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <div class="mt-2 flex items-center justify-between">
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

                                @if($product->variants->contains(fn ($v) => filled($v->ar_model_url)))
                                    <span class="text-[11px] font-medium text-sky-600 dark:text-sky-400">
                                        {{ __('AR available') }}
                                    </span>
                                @endif
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
