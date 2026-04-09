<x-layouts::app :title="$product->name">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ $product->name }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Products'), 'href' => route('products.index')],
                        ['label' => $product->name],
                    ]"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="ghost" icon="arrow-left" :href="route('products.index')" wire:navigate>
                    {{ __('Back to products') }}
                </flux:button>
                @if(auth()->user()?->isAdmin())
                    <flux:button
                        variant="primary"
                        icon="pencil-square"
                        :href="route('products.edit', $product)"
                        wire:navigate
                    >
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ── Left / main column ──────────────────────────────────────── --}}
            <div class="space-y-4 lg:col-span-2">

                {{-- Primary image --}}
                <div
                    class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    @php $primaryImage = $product->images->first(); @endphp
                    <div id="main-image-wrap" class="aspect-[4/3] w-full bg-zinc-100 dark:bg-zinc-800">
                        @if($primaryImage)
                            <img
                                id="main-image"
                                src="{{ $primaryImage->image_url }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <x-product-image-placeholder class="h-full w-full" />
                        @endif
                    </div>
                </div>

                {{-- Thumbnail strip —— clickable to swap main image --}}
                @if($product->images->count() > 1)
                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach($product->images as $index => $image)
                            <button
                                type="button"
                                onclick="swapMainImage(this, '{{ $image->image_url }}')"
                                class="h-20 w-28 flex-shrink-0 overflow-hidden rounded-md border-2 bg-zinc-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:bg-zinc-800 {{ $index === 0 ? 'border-sky-500' : 'border-zinc-200 hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500' }}"
                            >
                                <img
                                    src="{{ $image->image_url }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Description --}}
                @if($product->description)
                    <div
                        class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Description') }}
                        </h2>
                        <div class="text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- Variants table --}}
                @if($product->variants->count() > 0)
                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Variants') }}
                            </h2>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $product->variants->count() }}
                                {{ \Illuminate\Support\Str::plural('variant', $product->variants->count()) }}
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[36rem] text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-100 bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                        <th class="px-4 py-3">{{ __('SKU') }}</th>
                                        <th class="px-4 py-3">{{ __('Color') }}</th>
                                        <th class="px-4 py-3">{{ __('Frame size') }}</th>
                                        <th class="px-4 py-3">{{ __('Material') }}</th>
                                        <th class="px-4 py-3">{{ __('Lens type') }}</th>
                                        @if(auth()->user()?->isAdminOrStaff())
                                            <th class="px-4 py-3 text-end">{{ __('Stock') }}</th>
                                        @endif
                                        <th class="px-4 py-3">{{ __('') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach($product->variants as $variant)
                                        <tr class="bg-white dark:bg-zinc-900">
                                            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $variant->sku }}
                                            </td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                                {{ $variant->color ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                                {{ $variant->frame_size ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                                {{ $variant->material ?: '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                                {{ $variant->lens_type ?: '—' }}
                                            </td>
                                            @if(auth()->user()?->isAdminOrStaff())
                                                <td class="px-4 py-3 text-end tabular-nums">
                                                    @if($variant->inventory)
                                                        <span class="{{ $variant->inventory->isLowStock() ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }} font-medium">
                                                            {{ $variant->inventory->quantity }}
                                                        </span>
                                                        @if($variant->inventory->isLowStock())
                                                            <span class="ml-1 text-[10px] text-amber-500 dark:text-amber-400">{{ __('Low') }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-4 py-3">
                                                @if($variant->is_default)
                                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-800 dark:bg-sky-950/70 dark:text-sky-300">
                                                        {{ __('Default') }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>

            {{-- ── Right / sidebar ─────────────────────────────────────────── --}}
            <div class="space-y-4">
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    {{-- Price + status --}}
                    <div class="flex items-baseline justify-between">
                        <div class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                            {{ \App\Support\Money::peso($product->price ?? 0) }}
                        </div>

                        @if($product->is_active ?? true)
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200">
                                {{ __('Active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                                {{ __('Inactive') }}
                            </span>
                        @endif
                    </div>

                    {{-- Rating --}}
                    @php
                        $reviewCount  = $product->feedbacks->count();
                        $avgRating    = $reviewCount ? round($product->feedbacks->avg('rating'), 1) : 0;
                        $starsFilled  = (int) round($avgRating);
                    @endphp
                    @if($reviewCount > 0)
                        <div class="mt-2 flex items-center gap-1.5">
                            <div class="flex gap-0.5">
                                @for($s = 1; $s <= 5; $s++)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 {{ $s <= $starsFilled ? 'text-amber-400' : 'text-zinc-300 dark:text-zinc-600' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $avgRating }} / 5
                                ({{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }})
                            </span>
                        </div>
                    @else
                        <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">{{ __('No reviews yet') }}</p>
                    @endif

                    {{-- Detail list --}}
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Brand') }}</dt>
                            <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $product->brand ?: '—' }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Category') }}</dt>
                            <dd class="text-end text-zinc-900 dark:text-zinc-100">
                                {{ $product->category?->name ?? __('Uncategorized') }}
                            </dd>
                        </div>

                        @if($product->supplier)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Supplier') }}</dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $product->supplier->name }}</dd>
                            </div>
                        @endif

                        @if(auth()->user()?->isAdminOrStaff() && $product->cost_per_unit !== null)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Cost per unit') }}</dt>
                                <dd class="text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Support\Money::peso($product->cost_per_unit) }}
                                </dd>
                            </div>
                        @endif

                        @php $defaultVar = $product->defaultVariant; @endphp
                        @if($defaultVar?->lens_type)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Lens type') }}</dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $defaultVar->lens_type }}</dd>
                            </div>
                        @endif

                        @if($defaultVar?->material)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Material') }}</dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $defaultVar->material }}</dd>
                            </div>
                        @endif

                        @if($defaultVar?->base_curve)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Base curve') }}</dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $defaultVar->base_curve }}</dd>
                            </div>
                        @endif

                        @if($defaultVar?->diameter)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">{{ __('Diameter') }}</dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $defaultVar->diameter }}</dd>
                            </div>
                        @endif
                    </dl>

                    {{-- Stock widget (admin / staff) --}}
                    @if(auth()->user()?->isAdminOrStaff())
                        @php
                            $totalStock  = $product->variants->sum(fn ($v) => $v->inventory?->quantity ?? 0);
                            $hasLowStock = $product->variants->contains(fn ($v) => $v->inventory?->isLowStock());
                        @endphp
                        <div
                            class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-800/50"
                        >
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ __('Total stock') }}
                            </div>
                            <div class="mt-1 flex items-baseline justify-between gap-2">
                                <span class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $totalStock }}
                                </span>
                                @if($hasLowStock)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-900 dark:bg-amber-950/80 dark:text-amber-200">
                                        {{ __('Low stock') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">{{ __('Across all variants') }}</p>
                            @if(auth()->user()?->isAdmin())
                                <flux:button
                                    class="mt-3 w-full"
                                    size="sm"
                                    variant="ghost"
                                    :href="route('inventory.edit', $product)"
                                    wire:navigate
                                >
                                    {{ __('Adjust stock') }}
                                </flux:button>
                            @endif
                        </div>
                    @endif

                    {{-- AR badge (any variant may supply a model URL) --}}
                    @php
                        $arModelDisplay = $product->variants->first(fn ($v) => filled($v->ar_model_url))?->ar_model_url;
                    @endphp
                    @if($arModelDisplay)
                        <div
                            class="mt-4 rounded-lg border border-sky-200 bg-sky-50 p-3 dark:border-sky-900/60 dark:bg-sky-950/50"
                        >
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-sky-800 dark:text-sky-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                {{ __('AR virtual try-on available') }}
                            </div>
                            @if(auth()->user()?->isAdmin())
                                <p class="mt-1 break-all text-[11px] text-sky-700 dark:text-sky-400">
                                    {{ $arModelDisplay }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script>
        function swapMainImage(btn, url) {
            const img = document.getElementById('main-image');
            if (img) img.src = url;
            document.querySelectorAll('[onclick^="swapMainImage"]').forEach(function (b) {
                b.classList.remove('border-sky-500');
                b.classList.add('border-zinc-200', 'dark:border-zinc-700');
                b.classList.remove('border-zinc-200');
            });
            btn.classList.remove('border-zinc-200', 'dark:border-zinc-700');
            btn.classList.add('border-sky-500');
        }
    </script>
</x-layouts::app>
