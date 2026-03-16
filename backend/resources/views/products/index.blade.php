<x-layouts::app :title="__('Products')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Products') }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-300">
                    {{ __('Browse and manage products in your optical inventory.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-2">
                @if(auth()->user()?->isAdmin())
                    <flux:button variant="outline" icon="adjustments-horizontal">
                        {{ __('Filters') }}
                    </flux:button>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-4 shadow-[0_18px_45px_rgba(0,0,0,0.12)]">
            <form
                method="GET"
                action="{{ route('products.index') }}"
                class="flex flex-col gap-4 md:flex-row md:items-end"
            >
                <div class="flex-1">
                    <label for="search" class="mb-1 block text-sm font-medium text-[#111827]">
                        {{ __('Search') }}
                    </label>
                    <flux:input
                        id="search"
                        name="search"
                        :label="false"
                        placeholder="{{ __('Search by name, brand, or SKU') }}"
                        value="{{ $filters['search'] ?? '' }}"
                        class="border border-neutral-300 [&>input]:!text-[#111827]"
                    />
                </div>

                <div class="w-full md:w-56">
                    <label class="mb-1 block text-sm font-medium text-[#111827]">
                        {{ __('Category') }}
                    </label>
                    <select
                        name="category_id"
                        class="block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    >
                        <option value="">{{ __('All') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-4 md:ml-auto">
                    @if(auth()->user()?->isAdmin())
                        <label class="flex items-center gap-2 text-sm text-neutral-700">
                            <input
                                type="checkbox"
                                name="include_inactive"
                                value="1"
                                @checked(($filters['include_inactive'] ?? false))
                                class="h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span>{{ __('Include inactive') }}</span>
                        </label>
                    @endif

                    <div class="ml-auto flex gap-2">
                        <flux:button type="submit" variant="primary">
                            {{ __('Apply') }}
                        </flux:button>
                        <a href="{{ route('products.index') }}">
                            <flux:button type="button" variant="ghost">
                                {{ __('Reset') }}
                            </flux:button>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex-1 rounded-xl border border-neutral-200 bg-white p-4 shadow-[0_20px_50px_rgba(0,0,0,0.16)]">
            @if($products->isEmpty())
                <div class="py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('No products found. Try adjusting your filters.') }}
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($products as $product)
                        <a
                            href="{{ route('products.show', $product) }}"
                            class="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-[0_16px_40px_rgba(0,0,0,0.14)] transition hover:-translate-y-0.5 hover:shadow-[0_22px_55px_rgba(0,0,0,0.20)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            @php
                                $imageUrl = $product->images->first()->image_url ?? null;
                            @endphp
                            <div class="aspect-[4/3] w-full bg-neutral-100 transition group-hover:opacity-95">
                                @if($imageUrl)
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $product->name }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <x-placeholder-pattern class="h-full w-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                                @endif
                            </div>

                            <div class="flex flex-1 flex-col gap-2 p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="text-sm font-semibold text-neutral-900 line-clamp-2 group-hover:text-indigo-600">
                                            {{ $product->name }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-neutral-500">
                                            {{ $product->brand ?? '—' }}
                                            @if($product->sku)
                                                <span class="mx-1">•</span>
                                                <span>{{ $product->sku }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="text-right text-sm font-semibold text-neutral-900">
                                        {{ number_format((float) ($product->price ?? 0), 2) }}
                                    </div>
                                </div>

                                <div class="mt-1 text-xs text-neutral-500">
                                    {{ $product->category?->name ?? __('Uncategorized') }}
                                </div>

                                <div class="mt-2 flex items-center justify-between">
                                    @if($product->is_active ?? true)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-800">
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-700">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif

                                    @if($product->ar_model_url)
                                        <span class="text-[11px] font-medium text-indigo-600">
                                            {{ __('AR available') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-4 border-t border-neutral-200 pt-3 text-sm">
                    {{ $products->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>

