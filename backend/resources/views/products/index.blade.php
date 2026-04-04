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
                        <flux:button :href="route('products.index')" variant="ghost" wire:navigate>
                            {{ __('Reset') }}
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        <div
            class="flex-1 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @if($products->isEmpty())
                <div class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No products found. Try adjusting your filters.') }}
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
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div
                                            class="line-clamp-2 text-sm font-semibold text-zinc-900 group-hover:text-sky-600 dark:text-zinc-100 dark:group-hover:text-sky-400"
                                        >
                                            {{ $product->name }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">
                                            {{ $product->brand ?? '—' }}
                                            @if($product->sku)
                                                <span class="mx-1">•</span>
                                                <span>{{ $product->sku }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="text-right text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                                        {{ number_format((float) ($product->price ?? 0), 2) }}
                                    </div>
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
