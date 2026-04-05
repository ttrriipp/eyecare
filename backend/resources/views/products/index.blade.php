<x-layouts::app :title="__('Products')">
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
                    {{ __('Products') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Products')],
                    ]"
                />
            </div>

            @if(auth()->user()?->isAdmin())
                <flux:button variant="primary" icon="plus" :href="route('products.create')" wire:navigate>
                    {{ __('Add product') }}
                </flux:button>
            @endif
        </div>

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('products.index') }}"
                class="flex flex-col gap-4 md:flex-row md:items-end"
            >
                <input type="hidden" name="view" value="{{ $listView }}">
                <div class="min-w-0 flex-1">
                    <label for="search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Search') }}
                    </label>
                    <flux:input
                        id="search"
                        name="search"
                        :label="false"
                        placeholder="{{ __('Search by name, brand, or SKU') }}"
                        value="{{ $filters['search'] ?? '' }}"
                    />
                </div>

                <div class="w-full md:w-56">
                    <label for="category_id" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Category') }}
                    </label>
                    <select
                        id="category_id"
                        name="category_id"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="">{{ __('All') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-4 md:ml-auto">
                    @if(auth()->user()?->isAdmin())
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input
                                type="checkbox"
                                name="include_inactive"
                                value="1"
                                @checked(($filters['include_inactive'] ?? false))
                                class="h-4 w-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950"
                            >
                            <span>{{ __('Include inactive') }}</span>
                        </label>
                    @endif

                    <div class="ml-auto flex gap-2">
                        <flux:button type="submit" variant="primary">
                            {{ __('Apply') }}
                        </flux:button>
                        <flux:button :href="route('products.index', ['view' => $listView])" variant="ghost" wire:navigate>
                            {{ __('Reset') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        <div
            class="flex-1 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @php
                $toggleQuery = request()->except('page');
                $gridUrl = route('products.index', array_merge($toggleQuery, ['view' => 'grid']));
                $tableUrl = route('products.index', array_merge($toggleQuery, ['view' => 'table']));
            @endphp

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Display') }}
                </flux:text>
                <div
                    class="inline-flex rounded-lg border border-zinc-200 bg-zinc-50 p-0.5 dark:border-zinc-600 dark:bg-zinc-800"
                    role="group"
                    aria-label="{{ __('Product list layout') }}"
                >
                    <a
                        href="{{ $gridUrl }}"
                        wire:navigate
                        @class([
                            'rounded-md px-3 py-1.5 text-sm font-medium transition',
                            'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => ($listView ?? 'grid') === 'grid',
                            'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ($listView ?? 'grid') !== 'grid',
                        ])
                    >
                        {{ __('Grid') }}
                    </a>
                    <a
                        href="{{ $tableUrl }}"
                        wire:navigate
                        @class([
                            'rounded-md px-3 py-1.5 text-sm font-medium transition',
                            'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-50' => ($listView ?? 'grid') === 'table',
                            'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ($listView ?? 'grid') !== 'table',
                        ])
                    >
                        {{ __('Table') }}
                    </a>
                </div>
            </div>

            @if($products->isEmpty())
                <div class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No products found. Try adjusting your filters.') }}
                </div>
            @elseif(($listView ?? 'grid') === 'table')
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
                                <th class="px-4 py-3.5">{{ __('Status') }}</th>
                                <th class="px-4 py-3.5 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($products as $product)
                                @php
                                    $thumbUrl = $product->images->first()?->image_url;
                                @endphp
                                <tr class="bg-white hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/60">
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
                                        @if($product->ar_model_url)
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
                    {{ $products->withQueryString()->links() }}
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($products as $product)
                        <a
                            href="{{ route('products.show', $product) }}"
                            class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:shadow-lg dark:hover:shadow-zinc-950/50"
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

                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                                    {{ $product->category?->name ?? __('Uncategorized') }}
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

                                    @if($product->ar_model_url)
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
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
