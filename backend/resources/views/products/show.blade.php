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

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ $product->name }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ $product->brand ?? '—' }}
                    @if($product->sku)
                        <span class="mx-1">•</span>
                        <span>{{ $product->sku }}</span>
                    @endif
                </flux:text>
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
            <div class="space-y-4 lg:col-span-2">
                <div
                    class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    @php
                        $primaryImage = $product->images->first();
                    @endphp
                    <div class="aspect-[4/3] w-full bg-zinc-100 dark:bg-zinc-800">
                        @if($primaryImage)
                            <img
                                src="{{ $primaryImage->image_url }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <x-product-image-placeholder class="h-full w-full" />
                        @endif
                    </div>
                </div>

                @if($product->images->count() > 1)
                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach($product->images->slice(1) as $image)
                            <div
                                class="h-20 w-28 flex-shrink-0 overflow-hidden rounded-md border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800"
                            >
                                <img
                                    src="{{ $image->image_url }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                >
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($product->description)
                    <div
                        class="rounded-xl border border-zinc-200 bg-white p-4 text-sm leading-relaxed text-zinc-700 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:shadow-none"
                    >
                        {!! nl2br(e($product->description)) !!}
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <div class="flex items-baseline justify-between">
                        <div class="text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                            {{ number_format((float) ($product->price ?? 0), 2) }}
                        </div>

                        @if($product->is_active ?? true)
                            <span
                                class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200"
                            >
                                {{ __('Active') }}
                            </span>
                        @else
                            <span
                                class="inline-flex items-center rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200"
                            >
                                {{ __('Inactive') }}
                            </span>
                        @endif
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-zinc-500 dark:text-zinc-500">
                                {{ __('Category') }}
                            </dt>
                            <dd class="text-end text-zinc-900 dark:text-zinc-100">
                                {{ $product->category?->name ?? __('Uncategorized') }}
                            </dd>
                        </div>

                        @if($product->lens_type)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">
                                    {{ __('Lens type') }}
                                </dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">
                                    {{ $product->lens_type }}
                                </dd>
                            </div>
                        @endif

                        @if($product->frame_material)
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500 dark:text-zinc-500">
                                    {{ __('Frame material') }}
                                </dt>
                                <dd class="text-end text-zinc-900 dark:text-zinc-100">
                                    {{ $product->frame_material }}
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if(auth()->user()?->isAdminOrStaff())
                        @php
                            $inv = $product->inventory;
                        @endphp
                        <div
                            class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-800/50"
                        >
                            <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ __('Stock') }}
                            </div>
                            <div class="mt-1 flex items-baseline justify-between gap-2">
                                <span class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $inv ? $inv->quantity : '—' }}
                                </span>
                                @if($inv && $inv->isLowStock())
                                    <span
                                        class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-900 dark:bg-amber-950/80 dark:text-amber-200"
                                    >
                                        {{ __('Low') }}
                                    </span>
                                @endif
                            </div>
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

                    @if($product->ar_model_url)
                        <div
                            class="mt-4 rounded-lg border border-sky-200 bg-sky-50 p-3 text-xs text-sky-900 dark:border-sky-900/60 dark:bg-sky-950/50 dark:text-sky-200"
                        >
                            {{ __('This product supports AR preview.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
