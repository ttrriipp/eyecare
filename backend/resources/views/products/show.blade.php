@php
    $cat = $product->category;
    $isAdmin = auth()->user()?->isAdmin() ?? false;
    $isStaff = auth()->user()?->isAdminOrStaff() ?? false;
    $showAllVariants = $isStaff;
    $variantsForTable = $showAllVariants
        ? $product->variants
        : $product->variants->filter(fn ($v) => $v->is_active ?? true);
    $variantsForMetrics = $showAllVariants
        ? $product->variants
        : $variantsForTable;
    $heroVariant = $showAllVariants
        ? $product->defaultVariant
        : ($variantsForTable->sortBy('id')->first() ?? $product->defaultVariant);
    $thumbUrl = $heroVariant?->firstGalleryImage()?->image_url
        ?? $product->images->sortBy('sort_order')->first()?->image_url;
    $sortedImages = $product->images->sortBy('sort_order')->values();
    $sharedImages = $product->sharedImages;
    $variantLinkedImages = $product->images->whereNotNull('product_variant_id')->sortBy('sort_order')->values();
    $variantImageGroups = $variantLinkedImages->groupBy('product_variant_id');
    $hasMultipleImages = $sortedImages->count() > 1;
    $anyArUrl = $variantsForMetrics->contains(fn ($v) => filled($v->ar_model_url));
    $showArUi = (bool) ($cat?->has_ar_support ?? false);

    $totalStock = (int) $variantsForMetrics->sum(fn ($v) => $v->inventory?->quantity ?? 0);
    $outVariantCount = $variantsForMetrics->filter(fn ($v) => ($v->inventory?->quantity ?? 0) === 0)->count();
    $lowVariantCount = $variantsForMetrics->filter(function ($v) {
        $inv = $v->inventory;
        if (! $inv) {
            return false;
        }

        return $inv->quantity > 0 && $inv->quantity <= $inv->reorder_level;
    })->count();
    $arModelsCount = $showArUi
        ? $variantsForMetrics->filter(fn ($v) => filled($v->ar_model_url))->count()
        : 0;

    $reviewCount = $product->feedbacks->count();
    $avgRating = $reviewCount ? round($product->feedbacks->avg('rating'), 1) : 0;
    $starsFilled = (int) round($avgRating);
    $feedbackRedirectBack = route('products.show', $product, false).'?tab=feedback';

    $allowedTabs = ['info', 'variants', 'inventory', 'images', 'feedback'];
    $initialTab = request()->query('tab', 'info');
    if (! in_array($initialTab, $allowedTabs, true)) {
        $initialTab = 'info';
    }
    if (! $isStaff && in_array($initialTab, ['inventory', 'images'], true)) {
        $initialTab = 'info';
    }
    if (! $isAdmin && $initialTab === 'images') {
        $initialTab = 'info';
    }
@endphp

<x-layouts::app :title="$product->name">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl" id="product-detail-tabs" data-initial-tab="{{ $initialTab }}">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif
        @if($errors->has('variant'))
            <div
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/50 dark:text-red-100"
                role="alert"
            >
                {{ $errors->first('variant') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-app-breadcrumbs
                :items="[
                    ['label' => __('Home'), 'href' => route('dashboard')],
                    ['label' => __('Products'), 'href' => route('products.index')],
                    ['label' => $product->name],
                ]"
            />
            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="ghost" icon="arrow-left" :href="route('products.index')" wire:navigate>
                    {{ __('Back to products') }}
                </flux:button>
                @if($isAdmin)
                    @if($product->is_active ?? true)
                        <flux:modal.trigger name="confirm-deactivate-product">
                            <flux:button type="button" variant="danger" icon="eye-slash">
                                {{ __('Deactivate') }}
                            </flux:button>
                        </flux:modal.trigger>
                        <flux:modal name="confirm-deactivate-product" focusable class="max-w-xl">
                            <div class="space-y-2">
                                <flux:heading size="lg">{{ __('Deactivate this product?') }}</flux:heading>
                                <flux:subheading>
                                    {{ __('Inactive products are hidden from the storefront. You can activate the product again anytime from this page.') }}
                                </flux:subheading>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <form method="POST" action="{{ route('products.deactivate', $product) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <flux:button type="submit" variant="danger">{{ __('Deactivate product') }}</flux:button>
                                </form>
                            </div>
                        </flux:modal>
                    @else
                        <form method="POST" action="{{ route('products.activate', $product) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <flux:button type="submit" variant="primary" icon="eye">
                                {{ __('Activate product') }}
                            </flux:button>
                        </form>
                    @endif
                    <flux:button variant="primary" icon="pencil-square" :href="route('products.edit', $product)" wire:navigate>
                        {{ __('Edit product') }}
                    </flux:button>
                @endif
            </div>
        </div>

        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
            {{ $product->name }}
        </flux:heading>

        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="-mb-px flex flex-wrap gap-1" role="tablist" aria-label="{{ __('Product sections') }}">
                <button
                    type="button"
                    role="tab"
                    data-tab-button="info"
                    class="border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100"
                >
                    {{ __('Info') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    data-tab-button="variants"
                    class="border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100"
                >
                    {{ __('Variants') }}
                </button>
                @if($isStaff)
                    <button
                        type="button"
                        role="tab"
                        data-tab-button="inventory"
                        class="border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100"
                    >
                        {{ __('Inventory') }}
                    </button>
                @endif
                @if($isAdmin)
                    <button
                        type="button"
                        role="tab"
                        data-tab-button="images"
                        class="border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100"
                    >
                        {{ __('Images') }}
                    </button>
                @endif
                <button
                    type="button"
                    role="tab"
                    data-tab-button="feedback"
                    class="border-b-2 border-transparent px-4 py-2.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100"
                >
                    {{ __('Feedback') }}
                </button>
            </nav>
        </div>

        {{-- Info --}}
        <div data-tab-panel="info" class="hidden space-y-6">
            <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
                <div class="space-y-4 lg:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="shrink-0">
                                <div
                                    class="flex h-60 w-60 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 sm:h-72 sm:w-72"
                                >
                                    @if($thumbUrl)
                                        <img id="product-main-image" src="{{ $thumbUrl }}" alt="" class="max-h-full max-w-full object-contain">
                                    @else
                                        <x-product-image-placeholder :caption="false" class="h-full w-full rounded-lg" />
                                    @endif
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    @if($product->brand)
                                        <span>{{ $product->brand }}</span>
                                        <span class="text-zinc-400 dark:text-zinc-500" aria-hidden="true"> · </span>
                                    @endif
                                    <span>{{ $cat?->name ?? __('Uncategorized') }}</span>
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                    @if($product->is_active ?? true)
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200">
                                            {{ __('Active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-600 dark:text-zinc-200">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                    @if($showArUi && $anyArUrl)
                                        <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-medium text-purple-800 dark:bg-purple-950/70 dark:text-purple-200">
                                            {{ __('AR enabled') }}
                                        </span>
                                    @endif
                                </div>
                                @if($product->description)
                                    <div class="mt-4 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                                        {!! nl2br(e($product->description)) !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($hasMultipleImages)
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach($sortedImages as $index => $image)
                                <button
                                    type="button"
                                    onclick="productSwapMainImage(this, '{{ $image->image_url }}')"
                                    class="product-thumb-btn h-16 w-20 shrink-0 overflow-hidden rounded-md border-2 border-zinc-200 bg-zinc-100 transition dark:border-zinc-700 dark:bg-zinc-800 {{ $index === 0 ? 'border-sky-500' : '' }}"
                                >
                                    <img src="{{ $image->image_url }}" alt="" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-4 lg:sticky lg:top-4 lg:self-start">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Summary') }}
                        </h2>
                        <dl class="mt-3 space-y-2.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</dt>
                                <dd class="text-end font-medium text-zinc-900 dark:text-zinc-100">{{ $cat?->name ?? __('Uncategorized') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Brand') }}</dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $product->brand ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Variants') }}</dt>
                                <dd class="text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ trans_choice('{0} :count variants|{1} :count variant|[2,*] :count variants', $variantsForMetrics->count(), ['count' => $variantsForMetrics->count()]) }}
                                </dd>
                            </div>
                            @if($isStaff)
                                <div class="flex justify-between gap-4">
                                    <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Total stock') }}</dt>
                                    <dd class="text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ number_format($totalStock) }} {{ __('units') }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Low stock') }}</dt>
                                    <dd class="text-end tabular-nums font-medium text-amber-700 dark:text-amber-400">
                                        {{ trans_choice('{0} :count variants|{1} :count variant|[2,*] :count variants', $lowVariantCount, ['count' => $lowVariantCount]) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Out of stock') }}</dt>
                                    <dd class="text-end tabular-nums font-medium text-red-600 dark:text-red-400">
                                        {{ trans_choice('{0} :count variants|{1} :count variant|[2,*] :count variants', $outVariantCount, ['count' => $outVariantCount]) }}
                                    </dd>
                                </div>
                                @if($showArUi)
                                    <div class="flex justify-between gap-4">
                                        <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('AR models') }}</dt>
                                        <dd class="text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ trans_choice('{0} :count variants|{1} :count variant|[2,*] :count variants', $arModelsCount, ['count' => $arModelsCount]) }}
                                        </dd>
                                    </div>
                                @endif
                            @endif
                            <div class="flex justify-between gap-4">
                                <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Rating') }}</dt>
                                <dd class="text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    @if($reviewCount > 0)
                                        {{ number_format($avgRating, 1) }} / 5
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ __('Reviews') }}</dt>
                                <dd class="text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ trans_choice('{0} :count reviews|{1} :count review|[2,*] :count reviews', $reviewCount, ['count' => $reviewCount]) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Variants --}}
        <div data-tab-panel="variants" class="hidden space-y-4">
            @if($isAdmin)
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="space-y-1">
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Change prices, SKUs, or add rows from the editor.') }}</flux:text>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice('{0} :count shared images|{1} :count shared image|[2,*] :count shared images', $sharedImages->count(), ['count' => $sharedImages->count()]) }}
                            <span class="text-zinc-400 dark:text-zinc-500" aria-hidden="true">·</span>
                            {{ trans_choice('{0} :count variant-specific images|{1} :count variant-specific image|[2,*] :count variant-specific images', $variantLinkedImages->count(), ['count' => $variantLinkedImages->count()]) }}
                        </p>
                    </div>
                    <flux:button size="sm" variant="primary" icon="pencil-square" :href="route('products.edit', $product)" wire:navigate>
                        {{ __('Manage variants') }}
                    </flux:button>
                </div>
            @endif
            @if($variantsForTable->count() > 0)
                <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <table class="w-full min-w-[48rem] text-sm">
                        <thead>
                            <tr class="border-b border-zinc-100 bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                <th class="px-4 py-3 whitespace-nowrap">{{ __('Default') }}</th>
                                <th class="px-4 py-3">{{ __('SKU') }}</th>
                                @if($cat?->has_color)
                                    <th class="px-4 py-3">{{ __('Color') }}</th>
                                @endif
                                @if($cat?->has_frame_size)
                                    <th class="px-4 py-3">{{ __('Size') }}</th>
                                @endif
                                @if($cat?->has_material)
                                    <th class="px-4 py-3">{{ __('Material') }}</th>
                                @endif
                                @if($cat?->has_lens_type)
                                    <th class="px-4 py-3">{{ __('Lens type') }}</th>
                                @endif
                                @if($cat?->has_power_field)
                                    <th class="px-4 py-3">{{ __('Power') }}</th>
                                @endif
                                @if($cat?->has_duration)
                                    <th class="px-4 py-3">{{ __('Duration') }}</th>
                                @endif
                                <th class="px-4 py-3 text-end">{{ __('Price') }}</th>
                                @if($isStaff)
                                    <th class="px-4 py-3 text-end">{{ __('Cost') }}</th>
                                @endif
                                @if($isStaff)
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                @endif
                                @if($showArUi)
                                    <th class="px-4 py-3">{{ __('AR') }}</th>
                                @endif
                                @if($isAdmin)
                                    <th class="px-4 py-3 text-end whitespace-nowrap">{{ __('Actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($variantsForTable as $variant)
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="px-4 py-3 align-middle whitespace-nowrap">
                                        @if($variant->is_default)
                                            <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-800 dark:bg-sky-950/70 dark:text-sky-300">{{ __('Default') }}</span>
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $variant->sku }}</td>
                                    @if($cat?->has_color)
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->color ?: '—' }}</td>
                                    @endif
                                    @if($cat?->has_frame_size)
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->frame_size ?: '—' }}</td>
                                    @endif
                                    @if($cat?->has_material)
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->material ?: '—' }}</td>
                                    @endif
                                    @if($cat?->has_lens_type)
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->lens_type ?: '—' }}</td>
                                    @endif
                                    @if($cat?->has_power_field)
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->power ?: '—' }}</td>
                                    @endif
                                    @if($cat?->has_duration)
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->duration ?: '—' }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-end font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                        {{ \App\Support\Money::peso($variant->price ?? 0) }}
                                    </td>
                                    @if($isStaff)
                                        <td class="px-4 py-3 text-end tabular-nums text-zinc-700 dark:text-zinc-300">
                                            {{ $variant->cost_per_unit !== null ? \App\Support\Money::peso($variant->cost_per_unit) : '—' }}
                                        </td>
                                    @endif
                                    @if($isStaff)
                                        <td class="px-4 py-3">
                                            @if($variant->is_active ?? true)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200">{{ __('Active') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-600 dark:text-zinc-200">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($showArUi)
                                        <td class="px-4 py-3">
                                            @if(filled($variant->ar_model_url))
                                                <span class="text-xs text-purple-700 dark:text-purple-300">{{ __('Yes') }}</span>
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($isAdmin)
                                        <td class="px-4 py-3 text-end whitespace-nowrap">
                                            @if($variant->is_active ?? true)
                                                <flux:modal.trigger name="confirm-deactivate-variant-{{ $variant->id }}">
                                                    <flux:button type="button" size="sm" variant="danger">
                                                        {{ __('Deactivate') }}
                                                    </flux:button>
                                                </flux:modal.trigger>
                                            @else
                                                <form method="POST" action="{{ route('products.variants.activate', [$product, $variant]) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <flux:button type="submit" size="sm" variant="primary">
                                                        {{ __('Activate') }}
                                                    </flux:button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($isAdmin)
                    @foreach($variantsForTable as $variant)
                        @if($variant->is_active ?? true)
                            <flux:modal name="confirm-deactivate-variant-{{ $variant->id }}" focusable class="max-w-xl w-full overflow-x-hidden">
                                <div class="min-w-0 space-y-2 pe-2 sm:pe-4">
                                    <flux:heading size="lg" class="break-words pe-6">{{ __('Deactivate this variant?') }}</flux:heading>
                                    <flux:subheading class="break-words text-pretty">
                                        {{ __('Inactive variants are hidden from the storefront. At least one variant must stay active.') }}
                                    </flux:subheading>
                                </div>
                                <div class="mt-6 flex min-w-0 flex-wrap justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <form method="POST" action="{{ route('products.variants.deactivate', [$product, $variant]) }}" class="inline min-w-0">
                                        @csrf
                                        @method('PATCH')
                                        <flux:button type="submit" variant="danger">{{ __('Deactivate variant') }}</flux:button>
                                    </form>
                                </div>
                            </flux:modal>
                        @endif
                    @endforeach
                @endif
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No variants to display.') }}</p>
            @endif
        </div>

        {{-- Inventory --}}
        @if($isStaff)
            <div data-tab-panel="inventory" class="hidden space-y-8">
                @if($isAdmin && $variantsForTable->isNotEmpty())
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Reorder levels & batch / expiry') }}</h2>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Saves metadata only (no stock movement).') }}</p>
                        <form
                            id="product-inventory-meta-form"
                            method="POST"
                            action="{{ route('products.inventory.meta.update', $product) }}"
                            class="mt-4 space-y-4"
                        >
                            @csrf
                            @method('PUT')
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[56rem] text-sm">
                                    <thead>
                                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                            <th class="py-2 pe-3">{{ __('SKU') }}</th>
                                            <th class="py-2 pe-3">{{ __('Qty') }}</th>
                                            <th class="py-2 pe-3">{{ __('Reorder') }}</th>
                                            <th class="py-2 pe-3">{{ __('Reorder qty') }}</th>
                                            @if($cat?->requires_expiry_tracking)
                                                <th class="py-2 pe-3">{{ __('Batch') }}</th>
                                                <th class="py-2 pe-3">{{ __('Expires') }}</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach($variantsForTable as $v)
                                            @php $inv = $v->inventory; @endphp
                                            <tr>
                                                <td class="py-2 pe-3 font-mono text-xs">{{ $v->sku }}</td>
                                                <td class="py-2 pe-3 tabular-nums">{{ $inv?->quantity ?? 0 }}</td>
                                                <td class="py-2 pe-3">
                                                    <input type="hidden" name="rows[{{ $loop->index }}][variant_id]" value="{{ $v->id }}">
                                                    <input type="number" name="rows[{{ $loop->index }}][reorder_level]" value="{{ old('rows.'.$loop->index.'.reorder_level', $inv?->reorder_level ?? 5) }}" min="0" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                                </td>
                                                <td class="py-2 pe-3">
                                                    <input type="number" name="rows[{{ $loop->index }}][reorder_quantity]" value="{{ old('rows.'.$loop->index.'.reorder_quantity', $inv?->reorder_quantity ?? 0) }}" min="0" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                                </td>
                                                @if($cat?->requires_expiry_tracking)
                                                    <td class="py-2 pe-3">
                                                        <input type="text" name="rows[{{ $loop->index }}][batch_number]" value="{{ old('rows.'.$loop->index.'.batch_number', $inv?->batch_number ?? '') }}" class="w-28 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                                    </td>
                                                    <td class="py-2 pe-3">
                                                        <input type="date" name="rows[{{ $loop->index }}][expires_at]" value="{{ old('rows.'.$loop->index.'.expires_at', $inv?->expires_at?->format('Y-m-d') ?? '') }}" class="rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <flux:modal.trigger name="confirm-inventory-meta-save">
                                <flux:button type="button" variant="primary" size="sm">{{ __('Save inventory settings') }}</flux:button>
                            </flux:modal.trigger>
                        </form>

                        <flux:modal name="confirm-inventory-meta-save" focusable class="max-w-lg">
                            <div class="space-y-2 pr-8">
                                <flux:heading size="lg">{{ __('Save inventory settings?') }}</flux:heading>
                                <flux:subheading>
                                    {{ __('Reorder levels, batch, and expiry fields will be updated. On-hand quantity is not changed here.') }}
                                </flux:subheading>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" form="product-inventory-meta-form">
                                    {{ __('Save inventory settings') }}
                                </flux:button>
                            </div>
                        </flux:modal>
                    </div>
                @endif

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Manual stock adjustment') }}</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Use for received shipments, write-offs, or corrections.') }}</p>
                    @if($errors->hasAny(['product_variant_id', 'adjustment_type', 'quantity', 'reason', 'notes']))
                        <div
                            class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/50 dark:text-red-100"
                            role="alert"
                        >
                            <p class="font-medium">{{ __('Could not apply adjustment') }}</p>
                            <ul class="mt-1 list-inside list-disc text-xs">
                                @foreach($errors->only(['product_variant_id', 'adjustment_type', 'quantity', 'reason', 'notes']) as $fieldErrors)
                                    @foreach($fieldErrors as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    {{-- novalidate: confirm button lives in a modal; native required + hidden fields under the backdrop often blocks submit with no visible hint. --}}
                    <form
                        id="product-adjustment-form"
                        method="POST"
                        action="{{ route('inventory.update', $product) }}"
                        class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        novalidate
                    >
                        @csrf
                        @method('PUT')
                        @php
                            $adjustmentVariants = $product->variants->sortBy('id');
                            $adjustmentDefaultVariantId = $adjustmentVariants->first()?->id;
                        @endphp
                        <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('Variant') }}</label>
                            <select
                                name="product_variant_id"
                                class="@error('product_variant_id') border-red-500 ring-1 ring-red-500 @else border-zinc-300 dark:border-zinc-600 @enderror block w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                @foreach($adjustmentVariants as $v)
                                    <option value="{{ $v->id }}" @selected((string) old('product_variant_id', $adjustmentDefaultVariantId) === (string) $v->id)>{{ $v->sku }}</option>
                                @endforeach
                            </select>
                            @error('product_variant_id')
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('Type') }}</label>
                            <select
                                name="adjustment_type"
                                class="@error('adjustment_type') border-red-500 ring-1 ring-red-500 @else border-zinc-300 dark:border-zinc-600 @enderror block w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="add" @selected(old('adjustment_type', 'add') === 'add')>{{ __('Add (received, etc.)') }}</option>
                                <option value="subtract" @selected(old('adjustment_type') === 'subtract')>{{ __('Remove (damage, etc.)') }}</option>
                                <option value="set" @selected(old('adjustment_type') === 'set')>{{ __('Set quantity (correction)') }}</option>
                            </select>
                            @error('adjustment_type')
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('Quantity') }}</label>
                            <input
                                name="quantity"
                                type="number"
                                min="0"
                                step="1"
                                value="{{ old('quantity') }}"
                                class="@error('quantity') border-red-500 ring-1 ring-red-500 @else border-zinc-300 dark:border-zinc-600 @enderror block w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950 dark:text-zinc-100"
                            >
                            @error('quantity')
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('Reason') }}</label>
                            <select
                                name="reason"
                                class="@error('reason') border-red-500 ring-1 ring-red-500 @else border-zinc-300 dark:border-zinc-600 @enderror block w-full rounded-md border bg-white px-3 py-2 text-sm dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="" disabled @selected(old('reason') === null || old('reason') === ''))>{{ __('Select a reason') }}</option>
                                @foreach(\App\Enums\InventoryAdjustmentReason::cases() as $adjReason)
                                    <option value="{{ $adjReason->value }}" @selected(old('reason') === $adjReason->value)>{{ $adjReason->label() }}</option>
                                @endforeach
                            </select>
                            @error('reason')
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <flux:modal.trigger name="confirm-product-adjustment">
                                <flux:button type="button" variant="primary" size="sm">{{ __('Apply adjustment') }}</flux:button>
                            </flux:modal.trigger>
                        </div>
                    </form>

                    <flux:modal name="confirm-product-adjustment" focusable class="max-w-lg">
                        <div class="space-y-2 pr-8">
                            <flux:heading size="lg">{{ __('Apply this stock adjustment?') }}</flux:heading>
                            <flux:subheading>
                                {{ __('Inventory will be updated and logged in adjustment history.') }}
                            </flux:subheading>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary" form="product-adjustment-form">
                                {{ __('Apply adjustment') }}
                            </flux:button>
                        </div>
                    </flux:modal>
                </div>

                @if($adjustmentHistory !== null)
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Adjustment history') }}</h2>
                            <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('admin.inventory.adjustments', ['product' => $product->id])" wire:navigate>
                                {{ __('Full history') }}
                            </flux:button>
                        </div>
                        @if($adjustmentHistory->isEmpty())
                            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No adjustments yet.') }}</p>
                        @else
                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full min-w-[56rem] text-sm">
                                    <thead>
                                        <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                            <th class="py-2 pe-2">{{ __('Date') }}</th>
                                            <th class="py-2 pe-2">{{ __('SKU') }}</th>
                                            <th class="py-2 pe-2">{{ __('Type') }}</th>
                                            <th class="py-2 pe-2 text-end">{{ __('Before') }}</th>
                                            <th class="py-2 pe-2 text-end">{{ __('After') }}</th>
                                            <th class="py-2 pe-2 text-end">{{ __('Delta') }}</th>
                                            <th class="py-2 pe-2">{{ __('Reason') }}</th>
                                            <th class="py-2 pe-2">{{ __('By') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach($adjustmentHistory as $adj)
                                            @php $adjVariant = $adj->inventory?->productVariant; @endphp
                                            <tr>
                                                <td class="py-2 pe-2 whitespace-nowrap text-xs text-zinc-600 dark:text-zinc-400">{{ $adj->created_at->format('Y-m-d H:i') }}</td>
                                                <td class="py-2 pe-2 font-mono text-xs">{{ $adjVariant?->sku ?? '—' }}</td>
                                                <td class="py-2 pe-2 text-xs">{{ $adj->adjustment_type }}</td>
                                                <td class="py-2 pe-2 text-end tabular-nums">{{ $adj->quantity_before }}</td>
                                                <td class="py-2 pe-2 text-end tabular-nums">{{ $adj->quantity_after }}</td>
                                                <td class="py-2 pe-2 text-end tabular-nums font-medium {{ $adj->delta >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                                    {{ $adj->delta >= 0 ? '+' : '' }}{{ $adj->delta }}
                                                </td>
                                                <td class="py-2 pe-2 max-w-[12rem] truncate text-xs" title="{{ \App\Enums\InventoryAdjustmentReason::labelOrRaw($adj->reason) }}">{{ \App\Enums\InventoryAdjustmentReason::labelOrRaw($adj->reason) }}</td>
                                                <td class="py-2 pe-2 text-xs">{{ $adj->adjustedBy?->name ?? __('System') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- Images --}}
        @if($isAdmin)
            @php
                $sharedSorted = $sharedImages->sortBy('sort_order')->values();
                $variantsSorted = $product->variants->sortBy('id')->values();
            @endphp
            <div data-tab-panel="images" class="hidden space-y-8">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Images') }}</h2>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ trans_choice('{0} :count shared images|{1} :count shared image|[2,*] :count shared images', $sharedSorted->count(), ['count' => $sharedSorted->count()]) }}
                                <span class="text-zinc-400 dark:text-zinc-500" aria-hidden="true">·</span>
                                {{ trans_choice('{0} :count variant-specific images|{1} :count variant-specific image|[2,*] :count variant-specific images', $variantLinkedImages->count(), ['count' => $variantLinkedImages->count()]) }}
                            </p>
                            <p class="mt-2 max-w-xl text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                                {{ __('The first shared image is used in listings when a variant has no photos of its own. Reorder with the arrows on each card.') }}
                            </p>
                        </div>
                    </div>

                    <h3 class="mt-6 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Shared gallery') }}</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Shown for every SKU unless that SKU has its own images.') }}</p>

                    <form
                        id="form-upload-shared-image"
                        method="POST"
                        action="{{ route('products.images.store', $product) }}"
                        enctype="multipart/form-data"
                        class="mt-4"
                    >
                        @csrf
                        <label
                            id="dropzone-shared-image"
                            for="input-shared-image"
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50/80 px-4 py-8 transition hover:border-sky-400 hover:bg-sky-50/50 dark:border-zinc-600 dark:bg-zinc-800/40 dark:hover:border-sky-500 dark:hover:bg-sky-950/20"
                        >
                            <flux:icon name="photo" class="size-8 text-zinc-400 dark:text-zinc-500" />
                            <span class="text-center text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Drop an image here, or click to browse') }}</span>
                            <span class="text-center text-xs text-zinc-500 dark:text-zinc-400">{{ __('PNG, JPG, WebP or GIF · max 4 MB') }}</span>
                            <input id="input-shared-image" type="file" name="image" accept="image/*" required class="sr-only">
                        </label>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <flux:button type="submit" size="sm" variant="primary" icon="arrow-up-tray">{{ __('Upload shared image') }}</flux:button>
                            <span id="shared-file-label" class="hidden text-xs text-zinc-600 dark:text-zinc-400"></span>
                        </div>
                    </form>

                    <div class="mt-6 flex flex-wrap gap-4">
                        @forelse($sharedSorted as $idx => $img)
                            <div class="flex w-[11rem] flex-col overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/50 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                                <div class="relative aspect-square bg-zinc-100 dark:bg-zinc-800">
                                    <img src="{{ $img->image_url }}" alt="" class="h-full w-full object-cover">
                                    <form method="POST" action="{{ route('products.images.destroy', [$product, $img]) }}" class="absolute end-1 top-1" onsubmit="return confirm(@json(__('Remove this image?')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-red-600 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white shadow-sm hover:bg-red-700">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                                <div class="flex flex-1 flex-col gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                                    <p class="text-center text-[11px] font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">{{ __('Shared') }}</p>
                                    @if($sharedSorted->count() > 1)
                                        <div class="flex justify-center gap-1">
                                            <form method="POST" action="{{ route('products.images.move', [$product, $img]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="direction" value="up">
                                                <button
                                                    type="submit"
                                                    title="{{ __('Move earlier in gallery') }}"
                                                    class="rounded-md px-2 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-100 disabled:pointer-events-none disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                                    @if($idx === 0) disabled @endif
                                                >{{ __('Up') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('products.images.move', [$product, $img]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="direction" value="down">
                                                <button
                                                    type="submit"
                                                    title="{{ __('Move later in gallery') }}"
                                                    class="rounded-md px-2 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-100 disabled:pointer-events-none disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                                    @if($idx === $sharedSorted->count() - 1) disabled @endif
                                                >{{ __('Down') }}</button>
                                            </form>
                                        </div>
                                    @endif
                                    <flux:modal.trigger name="assign-image-{{ $img->id }}">
                                        <flux:button type="button" size="sm" variant="ghost" class="w-full">{{ __('Assign to variant…') }}</flux:button>
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No shared images yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Variant-specific images') }}</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('These override the shared gallery for that SKU only.') }}</p>

                    @if($variantsSorted->isEmpty())
                        <p class="mt-4 text-sm text-amber-800 dark:text-amber-200">{{ __('Add at least one variant before uploading SKU-specific images.') }}</p>
                    @else
                    <form
                        id="form-upload-variant-image"
                        method="POST"
                        action="{{ route('products.images.store', $product) }}"
                        enctype="multipart/form-data"
                        class="mt-4 space-y-3"
                    >
                        @csrf
                        <div class="max-w-md">
                            <label for="variant-upload-sku" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ __('SKU') }}</label>
                            <select
                                id="variant-upload-sku"
                                name="product_variant_id"
                                required
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                @foreach($variantsSorted as $v)
                                    <option value="{{ $v->id }}">{{ $v->sku }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label
                            id="dropzone-variant-image"
                            for="input-variant-image"
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50/80 px-4 py-8 transition hover:border-sky-400 hover:bg-sky-50/50 dark:border-zinc-600 dark:bg-zinc-800/40 dark:hover:border-sky-500 dark:hover:bg-sky-950/20"
                        >
                            <flux:icon name="photo" class="size-8 text-zinc-400 dark:text-zinc-500" />
                            <span class="text-center text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Drop an image here, or click to browse') }}</span>
                            <span class="text-center text-xs text-zinc-500 dark:text-zinc-400">{{ __('PNG, JPG, WebP or GIF · max 4 MB') }}</span>
                            <input id="input-variant-image" type="file" name="image" accept="image/*" required class="sr-only">
                        </label>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button type="submit" size="sm" variant="primary" icon="arrow-up-tray">{{ __('Upload for selected SKU') }}</flux:button>
                            <span id="variant-file-label" class="hidden text-xs text-zinc-600 dark:text-zinc-400"></span>
                        </div>
                    </form>
                    @endif

                    <div class="mt-8 space-y-8">
                        @php $hasAnyVariantImages = false; @endphp
                        @foreach($variantsSorted as $variant)
                            @php
                                $imgs = ($variantImageGroups->get($variant->id) ?? collect())->sortBy('sort_order')->values();
                            @endphp
                            @if($imgs->isNotEmpty())
                                @php $hasAnyVariantImages = true; @endphp
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">
                                        {{ $variant->sku }}
                                        <span class="font-normal normal-case text-zinc-500 dark:text-zinc-500">
                                            · {{ trans_choice('{0} :count images|{1} :count image|[2,*] :count images', $imgs->count(), ['count' => $imgs->count()]) }}
                                        </span>
                                    </h3>
                                    <div class="mt-3 flex flex-wrap gap-4">
                                        @foreach($imgs as $vIdx => $img)
                                            <div class="flex w-[11rem] flex-col overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50/50 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                                                <div class="relative aspect-square bg-zinc-100 dark:bg-zinc-800">
                                                    <img src="{{ $img->image_url }}" alt="" class="h-full w-full object-cover">
                                                    <form method="POST" action="{{ route('products.images.destroy', [$product, $img]) }}" class="absolute end-1 top-1" onsubmit="return confirm(@json(__('Remove this image?')));">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-md bg-red-600 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white shadow-sm hover:bg-red-700">{{ __('Delete') }}</button>
                                                    </form>
                                                </div>
                                                <div class="flex flex-1 flex-col gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                                                    <p class="truncate text-center font-mono text-[11px] font-medium text-zinc-800 dark:text-zinc-200" title="{{ $variant->sku }}">{{ $variant->sku }}</p>
                                                    @if($imgs->count() > 1)
                                                        <div class="flex justify-center gap-1">
                                                            <form method="POST" action="{{ route('products.images.move', [$product, $img]) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="direction" value="up">
                                                                <button
                                                                    type="submit"
                                                                    class="rounded-md px-2 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-100 disabled:pointer-events-none disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                                                    @if($vIdx === 0) disabled @endif
                                                                >{{ __('Up') }}</button>
                                                            </form>
                                                            <form method="POST" action="{{ route('products.images.move', [$product, $img]) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="direction" value="down">
                                                                <button
                                                                    type="submit"
                                                                    class="rounded-md px-2 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-100 disabled:pointer-events-none disabled:opacity-40 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                                                    @if($vIdx === $imgs->count() - 1) disabled @endif
                                                                >{{ __('Down') }}</button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                    <flux:modal.trigger name="assign-image-{{ $img->id }}">
                                                        <flux:button type="button" size="sm" variant="ghost" class="w-full">{{ __('Assign to…') }}</flux:button>
                                                    </flux:modal.trigger>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        @if(! $hasAnyVariantImages)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No variant-specific images yet.') }}</p>
                        @endif
                    </div>
                </div>

                @foreach($sharedSorted->concat($variantLinkedImages) as $img)
                    @php
                        $currentVariant = $img->product_variant_id
                            ? $variantsSorted->firstWhere('id', $img->product_variant_id)
                            : null;
                    @endphp
                    <flux:modal name="assign-image-{{ $img->id }}" focusable class="max-w-md w-full overflow-x-hidden">
                        <div class="min-w-0 space-y-2 pe-2 sm:pe-4">
                            <flux:heading size="lg" class="break-words pe-6">{{ __('Assign image') }}</flux:heading>
                            <flux:subheading class="break-words text-pretty">
                                {{ __('Choose where this image appears. Shared images are used as a fallback for every SKU; variant-specific images show only for that SKU.') }}
                            </flux:subheading>
                        </div>
                        <div class="mt-4 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <img src="{{ $img->image_url }}" alt="" class="max-h-48 w-full object-contain bg-zinc-50 dark:bg-zinc-900">
                        </div>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Currently:') }}
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $currentVariant ? $currentVariant->sku : __('Shared gallery') }}
                            </span>
                        </p>
                        <form method="POST" action="{{ route('products.images.update', [$product, $img]) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="assign-target-{{ $img->id }}" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ __('Assign to') }}</label>
                                <select
                                    id="assign-target-{{ $img->id }}"
                                    name="product_variant_id"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                >
                                    <option value="" @selected($img->product_variant_id === null)>{{ __('Shared gallery') }}</option>
                                    @foreach($variantsSorted as $v)
                                        <option value="{{ $v->id }}" @selected($img->product_variant_id === $v->id)>{{ $v->sku }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-wrap justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary">{{ __('Save assignment') }}</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endforeach
            </div>
        @endif

        {{-- Feedback --}}
        <div data-tab-panel="feedback" class="hidden space-y-4">
            @if($product->feedbacks->isNotEmpty())
                <ul class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-700 dark:bg-zinc-900">
                    @foreach($product->feedbacks as $fb)
                        <li class="px-5 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($isStaff)
                                            @if($fb->is_visible)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200">{{ __('Visible') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">{{ __('Hidden') }}</span>
                                            @endif
                                        @endif
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $fb->user?->name ?? __('Customer') }}</span>
                                        <span class="inline-flex items-center justify-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900 dark:bg-amber-950/80 dark:text-amber-200">
                                            {{ $fb->rating }}/5
                                        </span>
                                        <time class="text-xs text-zinc-500" datetime="{{ $fb->created_at?->toIso8601String() }}">
                                            {{ $fb->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        </time>
                                    </div>
                                    @if($fb->comment)
                                        <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $fb->comment }}</p>
                                    @endif
                                    @if(filled($fb->admin_reply))
                                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-900/80">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                                {{ __('Clinic reply') }}
                                            </p>
                                            <p class="mt-1 whitespace-pre-wrap text-zinc-800 dark:text-zinc-200">{{ $fb->admin_reply }}</p>
                                            @if($isStaff && ($fb->moderated_at || $fb->moderator))
                                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                    @if($fb->moderator)
                                                        {{ __('Last saved by :name', ['name' => $fb->moderator->name]) }}
                                                    @endif
                                                    @if($fb->moderated_at)
                                                        {{ $fb->moderator ? ' · ' : '' }}{{ $fb->moderated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @if($isStaff)
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            icon="ellipsis-vertical"
                                            class="shrink-0"
                                            title="{{ __('Actions') }}"
                                        >
                                            <span class="sr-only">{{ __('Actions') }}</span>
                                        </flux:button>
                                        <flux:menu>
                                            <flux:modal.trigger name="reply-feedback-{{ $fb->id }}">
                                                <flux:menu.item as="button" type="button" icon="chat-bubble-left-right">
                                                    {{ __('Reply') }}
                                                </flux:menu.item>
                                            </flux:modal.trigger>
                                            @if($fb->is_visible)
                                                <form method="POST" action="{{ route('feedbacks.visibility', $fb) }}" class="contents">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="is_visible" value="0">
                                                    <input type="hidden" name="redirect_to" value="{{ $feedbackRedirectBack }}">
                                                    <flux:menu.item as="button" type="submit" icon="eye-slash">
                                                        {{ __('Hide from product page') }}
                                                    </flux:menu.item>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('feedbacks.visibility', $fb) }}" class="contents">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="is_visible" value="1">
                                                    <input type="hidden" name="redirect_to" value="{{ $feedbackRedirectBack }}">
                                                    <flux:menu.item as="button" type="submit" icon="eye">
                                                        {{ __('Show on product page') }}
                                                    </flux:menu.item>
                                                </form>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if($isStaff)
                    @foreach($product->feedbacks as $fb)
                        <flux:modal name="reply-feedback-{{ $fb->id }}" focusable class="max-w-2xl">
                            <form method="POST" action="{{ route('feedbacks.reply', $fb) }}" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="redirect_to" value="{{ $feedbackRedirectBack }}">
                                <div class="pr-8">
                                    <flux:heading size="lg">{{ __('Clinic reply') }}</flux:heading>
                                    <flux:subheading class="mt-1">
                                        {{ $product->name }} · {{ $fb->user?->name ?? '—' }}
                                    </flux:subheading>
                                </div>
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-900/80">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                        {{ __('Customer review') }}
                                    </p>
                                    <p class="mt-1 text-zinc-800 dark:text-zinc-200">{{ __('Rating') }}: {{ $fb->rating }}/5</p>
                                    @if(filled($fb->comment))
                                        <p class="mt-2 whitespace-pre-wrap text-zinc-700 dark:text-zinc-300">{{ $fb->comment }}</p>
                                    @else
                                        <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('No written comment.') }}</p>
                                    @endif
                                </div>
                                @if(filled($fb->admin_reply) && ($fb->moderated_at || $fb->moderator))
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        @if($fb->moderator)
                                            {{ __('Last saved by :name', ['name' => $fb->moderator->name]) }}
                                        @endif
                                        @if($fb->moderated_at)
                                            {{ $fb->moderator ? ' · ' : '' }}{{ $fb->moderated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        @endif
                                    </p>
                                @endif
                                <div>
                                    <label for="admin-reply-product-{{ $fb->id }}" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ __('Your reply to the customer') }}
                                    </label>
                                    <flux:textarea
                                        id="admin-reply-product-{{ $fb->id }}"
                                        name="admin_reply"
                                        rows="6"
                                        placeholder="{{ __('Thank the customer or address their feedback…') }}"
                                    >{{ $fb->admin_reply }}</flux:textarea>
                                    @error('admin_reply')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Leave empty and save to remove the reply.') }}
                                    </p>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="primary">{{ __('Save reply') }}</flux:button>
                                </div>
                            </form>
                        </flux:modal>
                    @endforeach
                @endif
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No reviews for this product yet.') }}</p>
            @endif
            @if($isStaff)
                <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square" :href="route('feedbacks.index', ['product_id' => $product->id])" wire:navigate>
                    {{ __('All feedback') }}
                </flux:button>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('product-detail-tabs');
            if (!root) return;
            const initial = root.dataset.initialTab || 'info';

            function showTab(tab) {
                root.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== tab);
                });
                root.querySelectorAll('[data-tab-button]').forEach(function (btn) {
                    const on = btn.getAttribute('data-tab-button') === tab;
                    btn.setAttribute('aria-selected', on ? 'true' : 'false');
                    btn.classList.toggle('border-sky-500', on);
                    btn.classList.toggle('text-sky-700', on);
                    btn.classList.toggle('dark:border-sky-400', on);
                    btn.classList.toggle('dark:text-sky-300', on);
                    btn.classList.toggle('text-zinc-500', !on);
                    btn.classList.toggle('dark:text-zinc-400', !on);
                });
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            }

            root.querySelectorAll('[data-tab-button]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    showTab(btn.getAttribute('data-tab-button'));
                });
            });

            showTab(initial);
        })();

        (function () {
            function bindDropZone(zoneId, inputId, labelId) {
                const zone = document.getElementById(zoneId);
                const input = document.getElementById(inputId);
                const label = labelId ? document.getElementById(labelId) : null;
                if (!zone || !input) return;

                function showFileName() {
                    if (label && input.files && input.files[0]) {
                        label.textContent = input.files[0].name;
                        label.classList.remove('hidden');
                    }
                }

                input.addEventListener('change', showFileName);

                ['dragenter', 'dragover'].forEach(function (ev) {
                    zone.addEventListener(ev, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        zone.classList.add('border-sky-500', 'bg-sky-50', 'dark:border-sky-400', 'dark:bg-sky-950/40');
                    });
                });

                ['dragleave', 'drop'].forEach(function (ev) {
                    zone.addEventListener(ev, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (ev !== 'drop') {
                            zone.classList.remove('border-sky-500', 'bg-sky-50', 'dark:border-sky-400', 'dark:bg-sky-950/40');
                        }
                    });
                });

                zone.addEventListener('drop', function (e) {
                    zone.classList.remove('border-sky-500', 'bg-sky-50', 'dark:border-sky-400', 'dark:bg-sky-950/40');
                    const dt = e.dataTransfer;
                    if (!dt || !dt.files || !dt.files.length) return;
                    const file = dt.files[0];
                    if (!file.type.match(/^image\//)) return;
                    try {
                        const buf = new DataTransfer();
                        buf.items.add(file);
                        input.files = buf.files;
                    } catch (err) {
                        return;
                    }
                    showFileName();
                });
            }

            bindDropZone('dropzone-shared-image', 'input-shared-image', 'shared-file-label');
            bindDropZone('dropzone-variant-image', 'input-variant-image', 'variant-file-label');
        })();

        function productSwapMainImage(btn, url) {
            const img = document.getElementById('product-main-image');
            if (img) {
                img.src = url;
            }
            document.querySelectorAll('.product-thumb-btn').forEach(function (b) {
                b.classList.remove('border-sky-500');
                b.classList.add('border-zinc-200', 'dark:border-zinc-700');
            });
            btn.classList.remove('border-zinc-200', 'dark:border-zinc-700');
            btn.classList.add('border-sky-500');
        }
    </script>
</x-layouts::app>
