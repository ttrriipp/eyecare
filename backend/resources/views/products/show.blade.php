<x-layouts::app :title="$product->name">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl text-[#111827]">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">
                    {{ $product->name }}
                </flux:heading>
                <flux:text muted>
                    {{ $product->brand ?? '—' }}
                    @if($product->sku)
                        <span class="mx-1">•</span>
                        <span>{{ $product->sku }}</span>
                    @endif
                </flux:text>
            </div>

            <div class="flex items-center gap-2">
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('products.index') }}" wire:navigate>
                    {{ __('Back to products') }}
                </flux:button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-[0_18px_45px_rgba(0,0,0,0.12)]">
                    @php
                        $primaryImage = $product->images->first();
                    @endphp
                    <div class="aspect-[4/3] w-full bg-neutral-100">
                        @if($primaryImage)
                            <img
                                src="{{ $primaryImage->image_url }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <x-placeholder-pattern class="h-full w-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                        @endif
                    </div>
                </div>

                @if($product->images->count() > 1)
                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach($product->images->slice(1) as $image)
                            <div class="h-20 w-28 flex-shrink-0 overflow-hidden rounded-md border border-neutral-200 bg-neutral-100">
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
                    <div class="rounded-xl border border-neutral-200 bg-white p-4 text-sm leading-relaxed text-neutral-700 shadow-[0_12px_35px_rgba(0,0,0,0.10)]">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-[0_18px_45px_rgba(0,0,0,0.12)]">
                    <div class="flex items-baseline justify-between">
                        <div class="text-2xl font-semibold text-neutral-900">
                            {{ number_format((float) ($product->price ?? 0), 2) }}
                        </div>

                        @if($product->is_active ?? true)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                {{ __('Active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                {{ __('Inactive') }}
                            </span>
                        @endif
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-neutral-500">
                                {{ __('Category') }}
                            </dt>
                            <dd class="text-neutral-900">
                                {{ $product->category?->name ?? __('Uncategorized') }}
                            </dd>
                        </div>

                        @if($product->lens_type)
                            <div class="flex justify-between">
                                <dt class="text-neutral-500">
                                    {{ __('Lens type') }}
                                </dt>
                                <dd class="text-neutral-900">
                                    {{ $product->lens_type }}
                                </dd>
                            </div>
                        @endif

                        @if($product->frame_material)
                            <div class="flex justify-between">
                                <dt class="text-neutral-500">
                                    {{ __('Frame material') }}
                                </dt>
                                <dd class="text-neutral-900">
                                    {{ $product->frame_material }}
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if($product->ar_model_url)
                        <div class="mt-4 rounded-lg bg-indigo-50 p-3 text-xs text-indigo-800">
                            {{ __('This product supports AR preview.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>

