@php
    $cat = $product->category;
    $showAllVariants = auth()->user()?->isAdminOrStaff();
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
@endphp

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
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">

            {{-- Left: overview + gallery strip + variants --}}
            <div class="space-y-4 lg:col-span-2">
                <div
                    class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        {{-- Compact primary image --}}
                        <div class="shrink-0">
                            <div
                                id="main-image-wrap"
                                class="flex h-44 w-44 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 sm:h-52 sm:w-52"
                            >
                                @if($thumbUrl)
                                    <img
                                        id="main-image"
                                        src="{{ $thumbUrl }}"
                                        alt="{{ $product->name }}"
                                        class="max-h-full max-w-full object-contain"
                                    >
                                @else
                                    <x-product-image-placeholder :caption="false" class="h-full w-full rounded-lg" />
                                @endif
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                                {{ $product->name }}
                            </flux:heading>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
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
                                @elseif($showArUi)
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ __('AR capable — no model yet') }}
                                    </span>
                                @endif

                                @if($cat?->has_frame_size)
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Has frame size') }}</span>
                                @endif
                                @if($cat?->has_color)
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Has color') }}</span>
                                @endif
                                @if($cat?->has_material)
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Has material') }}</span>
                                @endif
                                @if($cat?->has_lens_type)
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Has lens type') }}</span>
                                @endif
                                @if($cat?->has_power_field)
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Has power') }}</span>
                                @endif
                                @if($cat?->has_duration)
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Has duration') }}</span>
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
                                onclick="swapMainImage(this, '{{ $image->image_url }}')"
                                class="h-16 w-20 flex-shrink-0 overflow-hidden rounded-md border-2 bg-zinc-100 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:bg-zinc-800 {{ $index === 0 ? 'border-sky-500' : 'border-zinc-200 hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500' }}"
                            >
                                <img
                                    src="{{ $image->image_url }}"
                                    alt=""
                                    class="h-full w-full object-cover"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif

                @if($variantsForTable->count() > 0)
                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Variants') }}
                            </h2>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $variantsForTable->count() }}
                                {{ \Illuminate\Support\Str::plural('variant', $variantsForTable->count()) }}
                                @if($showAllVariants && $product->variants->count() !== $variantsForTable->count())
                                    <span class="text-zinc-400 dark:text-zinc-500">({{ __(':total total', ['total' => $product->variants->count()]) }})</span>
                                @endif
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[48rem] text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-100 bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
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
                                        @if(auth()->user()?->isAdminOrStaff())
                                            <th class="px-4 py-3 text-end">{{ __('Stock') }}</th>
                                        @endif
                                        @if(auth()->user()?->isAdminOrStaff())
                                            <th class="px-4 py-3">{{ __('Status') }}</th>
                                        @endif
                                        @if($showArUi)
                                            <th class="px-4 py-3">{{ __('AR') }}</th>
                                        @endif
                                        @if(auth()->user()?->isAdminOrStaff() && $cat?->requires_expiry_tracking)
                                            <th class="px-4 py-3">{{ __('Batch / lot') }}</th>
                                            <th class="px-4 py-3">{{ __('Expires') }}</th>
                                        @endif
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach($variantsForTable as $variant)
                                        @php
                                            $inv = $variant->inventory;
                                            $qty = $inv?->quantity ?? 0;
                                            $out = $qty === 0;
                                            $low = $qty > 0 && $inv && $qty <= $inv->reorder_level;
                                        @endphp
                                        <tr class="bg-white dark:bg-zinc-900">
                                            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $variant->sku }}
                                            </td>
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
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->power ?: ($variant->base_curve ? (string) $variant->base_curve : '—') }}</td>
                                            @endif
                                            @if($cat?->has_duration)
                                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $variant->duration ?: '—' }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-end font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                                {{ \App\Support\Money::peso($variant->price ?? 0) }}
                                            </td>
                                            @if(auth()->user()?->isAdminOrStaff())
                                                <td class="px-4 py-3 text-end tabular-nums">
                                                    @if($inv)
                                                        <span @class([
                                                            'font-medium',
                                                            'text-red-600 dark:text-red-400' => $out,
                                                            'text-amber-600 dark:text-amber-400' => $low,
                                                            'text-zinc-900 dark:text-zinc-100' => ! $out && ! $low,
                                                        ])>{{ $qty }}</span>
                                                        @if($out)
                                                            <span class="ml-1 text-[10px] font-medium text-red-500 dark:text-red-400">{{ __('Out') }}</span>
                                                        @elseif($low)
                                                            <span class="ml-1 text-[10px] font-medium text-amber-500 dark:text-amber-400">{{ __('Low') }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            @if(auth()->user()?->isAdminOrStaff())
                                                <td class="px-4 py-3">
                                                    @if($variant->is_active ?? true)
                                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-200">
                                                            {{ __('Active') }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-600 dark:text-zinc-200">
                                                            {{ __('Inactive') }}
                                                        </span>
                                                    @endif
                                                </td>
                                            @endif
                                            @if($showArUi)
                                                <td class="px-4 py-3">
                                                    @if(filled($variant->ar_model_url))
                                                        <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-medium text-purple-800 dark:bg-purple-950/70 dark:text-purple-200">
                                                            {{ __('Yes') }}
                                                        </span>
                                                    @else
                                                        <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            @if(auth()->user()?->isAdminOrStaff() && $cat?->requires_expiry_tracking)
                                                <td class="max-w-[8rem] truncate px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300" title="{{ $inv?->batch_number }}">
                                                    {{ $inv?->batch_number ?: '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                                    @if($inv?->expires_at)
                                                        {{ $inv->expires_at->toFormattedDateString() }}
                                                    @else
                                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap items-center justify-end gap-2">
                                                    @if($variant->is_default)
                                                        <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-800 dark:bg-sky-950/70 dark:text-sky-300">
                                                            {{ __('Default') }}
                                                        </span>
                                                    @endif
                                                    @if(auth()->user()?->isAdmin())
                                                        <flux:button
                                                            size="sm"
                                                            variant="ghost"
                                                            icon="pencil-square"
                                                            :href="route('inventory.edit', ['product' => $product, 'variant' => $variant->id])"
                                                            wire:navigate
                                                        >
                                                            <span class="sr-only">{{ __('Adjust stock') }}</span>
                                                        </flux:button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($product->feedbacks->isNotEmpty())
                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <div class="flex flex-col gap-2 border-b border-zinc-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Customer reviews') }}
                            </h2>
                            @if(auth()->user()?->isAdminOrStaff())
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="arrow-top-right-on-square"
                                    :href="route('feedbacks.index', ['product_id' => $product->id])"
                                    wire:navigate
                                >
                                    {{ __('All feedback') }}
                                </flux:button>
                            @endif
                        </div>
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($product->feedbacks as $fb)
                                <li class="px-5 py-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $fb->user?->name ?? __('Customer') }}
                                                </span>
                                                @if($fb->is_verified_purchase)
                                                    <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-800 dark:bg-sky-950/70 dark:text-sky-200">
                                                        {{ __('Verified purchase') }}
                                                    </span>
                                                @endif
                                                @if(auth()->user()?->isAdminOrStaff() && ! ($fb->is_visible ?? true))
                                                    <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-700 dark:bg-zinc-600 dark:text-zinc-200">
                                                        {{ __('Hidden') }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if(auth()->user()?->isAdminOrStaff() && $fb->user?->email)
                                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $fb->user->email }}</p>
                                            @endif
                                            <div class="mt-2 flex items-center gap-1.5">
                                                <div class="flex gap-0.5" aria-hidden="true">
                                                    @for($s = 1; $s <= 5; $s++)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 {{ $s <= (int) $fb->rating ? 'text-amber-400' : 'text-zinc-300 dark:text-zinc-600' }}" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                    @endfor
                                                </div>
                                                <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $fb->rating }}/5</span>
                                            </div>
                                        </div>
                                        <time
                                            class="shrink-0 text-xs tabular-nums text-zinc-500 dark:text-zinc-400 sm:text-end"
                                            datetime="{{ $fb->created_at?->toIso8601String() }}"
                                        >
                                            {{ $fb->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                        </time>
                                    </div>
                                    @if($fb->comment)
                                        <p class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $fb->comment }}</p>
                                    @endif
                                    @if(auth()->user()?->isAdminOrStaff() && filled($fb->admin_reply))
                                        <div class="mt-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800/60">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Admin reply') }}</span>
                                            <p class="mt-1 whitespace-pre-wrap text-zinc-800 dark:text-zinc-200">{{ $fb->admin_reply }}</p>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @elseif(auth()->user()?->isAdminOrStaff())
                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <div class="flex flex-col gap-2 border-b border-zinc-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Customer reviews') }}
                            </h2>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="arrow-top-right-on-square"
                                :href="route('feedbacks.index', ['product_id' => $product->id])"
                                wire:navigate
                            >
                                {{ __('All feedback') }}
                            </flux:button>
                        </div>
                        <p class="px-5 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No reviews for this product yet.') }}
                        </p>
                    </div>
                @endif

                @if($recentAdjustments !== null)
                    <div
                        class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <div class="flex flex-col gap-2 border-b border-zinc-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Stock adjustment history') }}
                            </h2>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="arrow-top-right-on-square"
                                :href="route('admin.inventory.adjustments', ['product' => $product->id])"
                                wire:navigate
                            >
                                {{ __('Full history') }}
                            </flux:button>
                        </div>
                        @if($recentAdjustments->isEmpty())
                            <p class="px-5 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No inventory adjustments recorded for this product yet.') }}
                            </p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[36rem] text-sm">
                                    <thead>
                                        <tr class="border-b border-zinc-100 bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400">
                                            <th class="px-4 py-3 whitespace-nowrap">{{ __('When') }}</th>
                                            <th class="px-4 py-3">{{ __('Variant') }}</th>
                                            <th class="px-4 py-3">{{ __('Reason') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('Change') }}</th>
                                            <th class="px-4 py-3 text-center">{{ __('New qty') }}</th>
                                            <th class="px-4 py-3">{{ __('By') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach($recentAdjustments as $adj)
                                            @php
                                                $adjVariant = $adj->inventory?->productVariant;
                                                $unit = $cat?->stock_unit ?? null;
                                            @endphp
                                            <tr class="bg-white dark:bg-zinc-900">
                                                <td class="px-4 py-3 text-xs tabular-nums text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $adj->created_at->format('M j, Y') }}</span>
                                                    <span class="block text-[11px] text-zinc-400 dark:text-zinc-500">{{ $adj->created_at->format('g:i A') }}</span>
                                                </td>
                                                <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ $adjVariant?->sku ?? '—' }}
                                                </td>
                                                <td class="max-w-[14rem] px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                                    <span class="line-clamp-2" title="{{ $adj->reason }}">
                                                        {{ \Illuminate\Support\Str::limit($adj->reason, 80) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span @class([
                                                        'text-sm font-semibold tabular-nums',
                                                        'text-emerald-600 dark:text-emerald-400' => $adj->delta >= 0,
                                                        'text-red-600 dark:text-red-400' => $adj->delta < 0,
                                                    ])>
                                                        {{ $adj->delta >= 0 ? '+' : '' }}{{ $adj->delta }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center tabular-nums">
                                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $adj->quantity_after }}</span>
                                                    @if($unit)
                                                        <span class="ml-1 text-xs text-zinc-400">{{ $unit }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                                    @if($adj->adjustedBy)
                                                        {{ $adj->adjustedBy->name }}
                                                    @else
                                                        <span class="text-xs text-zinc-400">{{ __('System') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($recentAdjustments->count() >= 20)
                                <p class="border-t border-zinc-100 px-5 py-3 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                                    {{ __('Showing the 20 most recent adjustments.') }}
                                    <a
                                        href="{{ route('admin.inventory.adjustments', ['product' => $product->id]) }}"
                                        class="font-medium text-sky-600 hover:underline dark:text-sky-400"
                                        wire:navigate
                                    >{{ __('Open full history') }}</a>
                                </p>
                            @endif
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right: summary, actions --}}
            <div class="space-y-4 lg:sticky lg:top-4 lg:self-start">
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                        {{ __('Summary') }}
                    </h2>
                    <dl class="mt-3 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</dt>
                            <dd class="text-end font-medium text-zinc-900 dark:text-zinc-100">{{ $cat?->name ?? __('Uncategorized') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Brand') }}</dt>
                            <dd class="text-end text-zinc-900 dark:text-zinc-100">{{ $product->brand ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Variants') }}</dt>
                            <dd class="text-end tabular-nums text-zinc-900 dark:text-zinc-100">{{ $variantsForTable->count() }}</dd>
                        </div>
                        @if(auth()->user()?->isAdminOrStaff())
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Total stock') }}</dt>
                                <dd class="text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($totalStock) }}
                                    @if($cat?->stock_unit)
                                        <span class="text-xs font-normal text-zinc-500">{{ $cat->stock_unit }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Low stock') }}</dt>
                                <dd class="text-end tabular-nums font-medium {{ $lowVariantCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ trans_choice('{0} :count|{1} :count variant|[2,*] :count variants', $lowVariantCount, ['count' => $lowVariantCount]) }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Out of stock') }}</dt>
                                <dd class="text-end tabular-nums font-medium {{ $outVariantCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ trans_choice('{0} :count|{1} :count variant|[2,*] :count variants', $outVariantCount, ['count' => $outVariantCount]) }}
                                </dd>
                            </div>
                        @endif
                        @if($showArUi)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('AR models') }}</dt>
                                <dd class="text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ trans_choice('{0} :count|{1} :count variant|[2,*] :count variants', $arModelsCount, ['count' => $arModelsCount]) }}
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if($reviewCount > 0)
                        <div class="mt-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">{{ __('Rating') }}</div>
                            <div class="mt-2 flex items-center gap-1.5">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 {{ $s <= $starsFilled ? 'text-amber-400' : 'text-zinc-300 dark:text-zinc-600' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $avgRating }} / 5 ({{ $reviewCount }})
                                </span>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 border-t border-zinc-100 pt-4 text-xs text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">{{ __('No reviews yet') }}</p>
                    @endif
                </div>

                @if(auth()->user()?->isAdmin())
                    <div
                        class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Actions') }}
                        </h2>
                        <div class="mt-3 flex flex-col gap-2">
                            <flux:button variant="ghost" icon="pencil-square" class="w-full" :href="route('products.edit', $product)" wire:navigate>
                                {{ __('Edit product') }}
                            </flux:button>
                            @if($product->is_active ?? true)
                                <div class="w-full">
                                    <flux:modal.trigger name="confirm-deactivate-product">
                                        <flux:button type="button" variant="danger" icon="eye-slash" class="w-full">
                                            {{ __('Deactivate') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                </div>
                            @else
                                <form method="POST" action="{{ route('products.activate', $product) }}" class="w-full">
                                    @csrf
                                    @method('PATCH')
                                    <flux:button
                                        type="submit"
                                        variant="outline"
                                        icon="check"
                                        class="w-full border-emerald-300 text-emerald-800 shadow-sm hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                                    >
                                        {{ __('Activate') }}
                                    </flux:button>
                                </form>
                            @endif
                        </div>

                        @if($product->is_active ?? true)
                            <flux:modal name="confirm-deactivate-product" focusable class="max-w-xl">
                                <div class="space-y-2 pr-8">
                                    <flux:heading size="lg">{{ __('Deactivate this product?') }}</flux:heading>
                                    <flux:subheading>
                                        {{ __('It will be hidden from the catalog until you activate it again.') }}
                                    </flux:subheading>
                                </div>
                                <div class="mt-6 flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <form method="POST" action="{{ route('products.deactivate', $product) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                                    </form>
                                </div>
                            </flux:modal>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function swapMainImage(btn, url) {
            const img = document.getElementById('main-image');
            if (img) img.src = url;
            document.querySelectorAll('button[onclick^="swapMainImage"]').forEach(function (b) {
                b.classList.remove('border-sky-500');
                b.classList.add('border-zinc-200', 'dark:border-zinc-700');
            });
            btn.classList.remove('border-zinc-200', 'dark:border-zinc-700');
            btn.classList.add('border-sky-500');
        }

    </script>
</x-layouts::app>
