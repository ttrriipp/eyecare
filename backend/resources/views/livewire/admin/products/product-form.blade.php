{{-- Single root element required by Livewire — backdrop + panel share this wrapper.
     Both children use position:fixed so this div has no visual footprint. --}}
<div>

{{-- ── Backdrop ─────────────────────────────────────────────────────────── --}}
<div
    wire:click="closePanel"
    aria-hidden="true"
    class="fixed inset-0 z-40 bg-black/40 transition-opacity duration-300 ease-in-out
           {{ $showPanel ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"
></div>

{{-- ── Slide-over panel ───────────────────────────────────────────────── --}}
<div
    role="dialog"
    aria-modal="true"
    class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[520px] flex-col bg-white shadow-2xl transition-transform duration-300 ease-in-out
           dark:bg-zinc-900 sm:border-l sm:border-zinc-200 dark:sm:border-zinc-700
           {{ $showPanel ? 'translate-x-0' : 'translate-x-full' }}"
>
    {{-- Panel header --}}
    <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
        <div>
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">
                {{ $mode === 'add' ? __('New product') : __('Edit product') }}
            </h2>
            @if($mode === 'add')
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Step') }} {{ $step }} {{ __('of') }} 2 —
                    {{ $step === 1 ? __('Product details') : __('Add variants') }}
                </p>
            @endif
        </div>
        <button type="button" wire:click="closePanel"
            class="flex h-8 w-8 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
        </button>
    </div>

    {{-- Step bar (add mode only) --}}
    @if($mode === 'add')
        <div class="flex shrink-0 border-b border-zinc-200 dark:border-zinc-700">
            <div @class(['flex-1 py-1.5 text-center text-xs font-medium transition', 'bg-sky-600 text-white' => $step === 1, 'text-zinc-500 dark:text-zinc-400' => $step !== 1])>
                1. {{ __('Details') }}
            </div>
            <div @class(['flex-1 py-1.5 text-center text-xs font-medium transition', 'bg-sky-600 text-white' => $step === 2, 'text-zinc-500 dark:text-zinc-400' => $step !== 2])>
                2. {{ __('Variants') }}
            </div>
        </div>
    @endif

    {{-- Scrollable body --}}
    <div class="flex-1 overflow-y-auto">

        {{-- ══════════════════════════════════════════════════════
             STEP 1 — Product details
             ══════════════════════════════════════════════════════ --}}
        @if($step === 1)
            <div class="space-y-4 p-5">

                {{-- Category --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Category') }} <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="category_id"
                        class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                               {{ $errors->has('category_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                               bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                        <option value="">{{ __('Select a category…') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                    {{-- Category notices --}}
                    @if($cat_requires_prescription)
                        <div class="mt-2 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-2 text-xs text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" /></svg>
                            {{ __('This category requires a prescription at checkout.') }}
                        </div>
                    @endif
                </div>

                {{-- Name --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Product name') }} <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.live="name" type="text" maxlength="100"
                        placeholder="{{ __('e.g. Titanium Semi-Rimless Frame') }}"
                        class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                               {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                               bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                    >
                    @error('name') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Brand --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Brand') }} <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.live="brand" type="text" maxlength="80"
                        placeholder="{{ __('e.g. Bolon, Hangten, Acuvue') }}"
                        class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                               {{ $errors->has('brand') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                               bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                    >
                    @error('brand') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Description') }} <span class="text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
                    </label>
                    <textarea wire:model="description" rows="3" maxlength="1000"
                        placeholder="{{ __('Brief product description…') }}"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                    ></textarea>
                </div>

                {{-- Selling price + Cost price --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Selling price') }} (₱) <span class="text-red-500">*</span>
                        </label>
                        <input wire:model.live="price" type="number" min="0" step="0.01"
                            placeholder="0.00"
                            class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                   {{ $errors->has('price') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                                   bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                        @error('price') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Cost price') }} (₱)
                            <span class="text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
                        </label>
                        <input wire:model.live="cost_per_unit" type="number" min="0" step="0.01"
                            placeholder="0.00"
                            class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                                   {{ $errors->has('cost_per_unit') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                                   bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                        @error('cost_per_unit') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Status') }}</label>
                    <div class="flex gap-4 pt-1">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="radio" wire:model="is_active" value="1" class="text-sky-600 focus:ring-sky-500">
                            {{ __('Active') }}
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="radio" wire:model="is_active" value="0" class="text-sky-600 focus:ring-sky-500">
                            {{ __('Inactive') }}
                        </label>
                    </div>
                </div>

                {{-- Low stock threshold --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Low stock threshold') }} <span class="text-red-500">*</span>
                    </label>
                    <input wire:model.live="low_stock_threshold" type="number" min="1" max="9999"
                        class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1
                               {{ $errors->has('low_stock_threshold') ? 'border-red-400 focus:border-red-500 focus:ring-red-500' : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                               bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100"
                    >
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __("You'll be alerted when any variant's stock falls below this number.") }}
                    </p>
                    @error('low_stock_threshold') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Variant images (edit mode): each sellable variant has its own gallery --}}
                @if($mode === 'edit' && auth()->user()?->isAdmin())
                    <div class="space-y-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Variant images') }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Images apply to each variant (SKU). The first image is used as the thumbnail for that variant.') }}</p>

                        @foreach($editVariantLabels as $variantId => $vlabel)
                            <div class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-3 dark:border-zinc-700 dark:bg-zinc-800/40">
                                <p class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $vlabel }}</p>

                                @php
                                    $ex = $existingVariantImages[$variantId] ?? [];
                                    $pe = $pendingVariantImagesEdit[$variantId] ?? [];
                                @endphp
                                @if(count($ex) > 0 || count($pe) > 0)
                                    <div class="mb-2 flex flex-wrap gap-2">
                                        @foreach($ex as $img)
                                            @php $isPrimary = $img['sort_order'] === 0; @endphp
                                            <div class="group relative" wire:key="ex-{{ $variantId }}-{{ $img['id'] }}">
                                                <img src="{{ $img['url'] }}" loading="lazy" alt=""
                                                     class="h-20 w-20 rounded-lg border object-cover {{ $isPrimary ? 'border-sky-400 dark:border-sky-500' : 'border-zinc-200 dark:border-zinc-600' }}">
                                                @if($isPrimary)
                                                    <span class="absolute bottom-1 left-1 rounded bg-sky-500/90 px-1 py-px text-[10px] font-semibold text-white">{{ __('Primary') }}</span>
                                                @else
                                                    <button type="button" wire:click="setPrimaryExistingImage({{ $variantId }}, {{ $img['id'] }})"
                                                        class="absolute bottom-1 left-1 hidden rounded bg-black/50 px-1 py-px text-[10px] font-medium text-white hover:bg-sky-600 group-hover:flex">★ {{ __('Primary') }}</button>
                                                @endif
                                                <button type="button" wire:click="removeExistingImage({{ $img['id'] }})"
                                                    class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white opacity-0 shadow transition group-hover:opacity-100 hover:bg-red-600" title="{{ __('Remove') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                        @foreach($pe as $pix => $pending)
                                            <div class="group relative" wire:key="pe-{{ $variantId }}-{{ $pix }}">
                                                <img src="{{ $pending->temporaryUrl() }}" alt="" class="h-20 w-20 rounded-lg border border-dashed border-emerald-400 object-cover dark:border-emerald-600">
                                                <span class="absolute bottom-1 left-1 rounded bg-emerald-500/90 px-1 py-px text-[10px] font-semibold text-white">{{ __('New') }}</span>
                                                @if($pix > 0)
                                                    <button type="button" wire:click="setPrimaryPendingEditUpload({{ $variantId }}, {{ $pix }})"
                                                        class="absolute bottom-1 left-1 hidden rounded bg-black/50 px-1 py-px text-[10px] font-medium text-white hover:bg-sky-600 group-hover:flex">★ {{ __('Primary') }}</button>
                                                @endif
                                                <button type="button" wire:click="removePendingEditUpload({{ $variantId }}, {{ $pix }})"
                                                    class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white opacity-0 shadow transition group-hover:opacity-100 hover:bg-red-600" title="{{ __('Remove') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if($cat_has_ar_support)
                                    <div class="mb-2">
                                        <label class="mb-1 flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                            {{ __('AR model URL') }}
                                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-1.5 py-0.5 text-[10px] font-medium text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">AR</span>
                                            <span class="font-normal text-zinc-400">({{ __('optional') }})</span>
                                        </label>
                                        <input wire:model.blur="variantArModelUrl.{{ $variantId }}" type="url"
                                            placeholder="https://…/model.glb"
                                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                        >
                                        @error('variantArModelUrl.'.$variantId) <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                <label class="flex cursor-pointer flex-col items-center gap-1 rounded-lg border border-dashed border-zinc-300 bg-white px-3 py-3 text-center dark:border-zinc-600 dark:bg-zinc-900/40">
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Add images for this variant') }}</span>
                                    <input type="file" wire:model="pendingVariantImagesEdit.{{ $variantId }}" accept="image/*" multiple class="sr-only">
                                </label>
                                <div wire:loading wire:target="pendingVariantImagesEdit.{{ $variantId }}" class="mt-1 text-[11px] text-zinc-500">{{ __('Uploading…') }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════
             STEP 2 — Add variants (add mode only)
             ══════════════════════════════════════════════════════ --}}
        @if($step === 2)
            <div class="space-y-5 p-5">

                <div>
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                        {{ __('Add variants for') }} <span class="text-sky-600 dark:text-sky-400">{{ $name }}</span>
                    </h3>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Add at least one variant. You can add more later from the product detail panel.') }}
                    </p>
                </div>

                {{-- Dynamic variant form fields --}}
                <div class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">

                    @if($cat_has_color)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Color / finish') }} <span class="text-red-500">*</span>
                            </label>
                            <input wire:model="v_color" type="text" maxlength="60"
                                placeholder="{{ __('e.g. Gold, Matte Black, Havana Brown') }}"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                            >
                            @error('v_color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($cat_has_frame_size)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Frame size') }} <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="v_frame_size"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option value="">{{ __('Select…') }}</option>
                                <option value="Small (50mm)">{{ __('Small (50mm)') }}</option>
                                <option value="Medium (54mm)">{{ __('Medium (54mm)') }}</option>
                                <option value="Large (56mm)">{{ __('Large (56mm)') }}</option>
                                <option value="XL (58mm)">{{ __('XL (58mm)') }}</option>
                            </select>
                            @error('v_frame_size') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($cat_has_material)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Material') }} <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="v_material"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option value="">{{ __('Select…') }}</option>
                                <option value="Acetate">Acetate</option>
                                <option value="Titanium">Titanium</option>
                                <option value="Metal">Metal</option>
                                <option value="TR-90">TR-90</option>
                                <option value="Polycarbonate">Polycarbonate</option>
                                <option value="High-index">High-index</option>
                                <option value="CR-39 Plastic">CR-39 Plastic</option>
                                <option value="Stainless Steel">Stainless Steel</option>
                            </select>
                            @error('v_material') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($cat_has_lens_type)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Lens type') }} <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="v_lens_type"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option value="">{{ __('Select…') }}</option>
                                @if(str_contains(strtolower($cat_name), 'sunglass'))
                                    <option value="Classic tint">Classic tint</option>
                                    <option value="Polarized">Polarized</option>
                                    <option value="Mirrored">Mirrored</option>
                                    <option value="Gradient">Gradient</option>
                                @elseif(str_contains(strtolower($cat_name), 'prescription lenses') || str_contains(strtolower($cat_name), 'prescription lens'))
                                    <option value="Single Vision">Single Vision</option>
                                    <option value="Bifocal">Bifocal</option>
                                    <option value="Progressive">Progressive</option>
                                    <option value="Reading">Reading</option>
                                @elseif(str_contains(strtolower($cat_name), 'contact'))
                                    <option value="Daily">Daily</option>
                                    <option value="Bi-weekly">Bi-weekly</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Quarterly">Quarterly</option>
                                @else
                                    <option value="Clear">Clear</option>
                                    <option value="Blue light filter">Blue light filter</option>
                                    <option value="Photochromic">Photochromic</option>
                                    <option value="Anti-radiation">Anti-radiation</option>
                                @endif
                            </select>
                            @error('v_lens_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if($cat_has_power_field)
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Base curve (mm)') }} <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="v_base_curve" type="number" step="0.1"
                                    placeholder="8.5"
                                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                >
                                @error('v_base_curve') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Diameter (mm)') }} <span class="text-red-500">*</span>
                                </label>
                                <input wire:model="v_diameter" type="number" step="0.1"
                                    placeholder="14.2"
                                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                >
                                @error('v_diameter') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Always shown --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Price adjustment') }} (₱)
                            </label>
                            <input wire:model="v_price_adjustment" type="number" step="0.01"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                            <p class="mt-0.5 text-[11px] text-zinc-400">{{ __('Added to base price. Use 0 if same.') }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Initial stock') }} ({{ $cat_stock_unit }})
                            </label>
                            <input wire:model="v_initial_stock" type="number" min="0"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                        </div>
                    </div>

                    @if($cat_has_ar_support)
                        <div>
                            <label class="mb-1 flex items-center gap-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('AR model URL') }}
                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-1.5 py-0.5 text-[10px] font-medium text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">AR</span>
                                <span class="font-normal text-zinc-400">({{ __('optional') }})</span>
                            </label>
                            <input wire:model="v_ar_model_url" type="url"
                                placeholder="https://…/model.glb"
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                            >
                            <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                {{ __('Applies to this variant row only. Add the variant to the list, then set another URL for the next row if needed.') }}
                            </p>
                            @error('v_ar_model_url') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Variant images') }} <span class="font-normal text-zinc-400">({{ __('optional') }})</span>
                        </label>
                        <p class="mb-2 text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('These photos apply to the variant you are about to add. You can add more later when editing the product.') }}</p>
                        @if(count($v_variant_images) > 0)
                            <div class="mb-2 flex flex-wrap gap-2">
                                @foreach($v_variant_images as $vix => $vf)
                                    <div class="group relative" wire:key="vv-{{ $vix }}">
                                        <img src="{{ $vf->temporaryUrl() }}" class="h-16 w-16 rounded-lg border border-zinc-200 object-cover dark:border-zinc-600" alt="">
                                        @if($vix > 0)
                                            <button type="button" wire:click="setPrimaryVVariantImage({{ $vix }})"
                                                class="absolute bottom-1 left-1 rounded bg-black/50 px-1 py-px text-[10px] font-medium text-white hover:bg-sky-600">★</button>
                                        @endif
                                        <button type="button" wire:click="removeVVariantImage({{ $vix }})"
                                            class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white opacity-0 shadow transition group-hover:opacity-100 hover:bg-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <label class="flex cursor-pointer flex-col items-center gap-1 rounded-lg border border-dashed border-zinc-300 bg-white px-3 py-3 text-center dark:border-zinc-600 dark:bg-zinc-900/30">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Click to add photos') }} · {{ __('PNG, JPG, WEBP · max 4 MB') }}</span>
                            <input type="file" wire:model="v_variant_images" accept="image/*" multiple class="sr-only">
                        </label>
                        <div wire:loading wire:target="v_variant_images" class="mt-1 text-[11px] text-zinc-500">{{ __('Uploading…') }}</div>
                    </div>

                    @if($variantError)
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $variantError }}</p>
                    @endif

                    <flux:button type="button" wire:click="addVariant" variant="ghost" icon="plus" class="w-full">
                        {{ __('Add this variant to list') }}
                    </flux:button>
                </div>

                {{-- Pending variants list --}}
                @if(!empty($pendingVariants))
                    <div class="space-y-2">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            {{ __('Variants to add') }} ({{ count($pendingVariants) }})
                        </h4>
                        @foreach($pendingVariants as $i => $v)
                            <div class="flex items-start justify-between gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="flex min-w-0 flex-1 gap-2">
                                    @php $pimgs = $pendingVariantImages[$i] ?? []; @endphp
                                    @if(count($pimgs) > 0)
                                        <div class="flex shrink-0 flex-wrap gap-1">
                                            @foreach($pimgs as $pi => $pfile)
                                                <div class="group relative" wire:key="pv-{{ $i }}-{{ $pi }}">
                                                    <img src="{{ $pfile->temporaryUrl() }}" class="h-12 w-12 rounded-md border border-zinc-200 object-cover dark:border-zinc-600" alt="">
                                                    @if($pi > 0)
                                                        <button type="button" wire:click="setPrimaryPendingVariantImage({{ $i }}, {{ $pi }})" class="absolute bottom-0 left-0 rounded bg-black/50 px-0.5 text-[9px] text-white opacity-0 group-hover:opacity-100">★</button>
                                                    @endif
                                                    <button type="button" wire:click="removePendingVariantImage({{ $i }}, {{ $pi }})" class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white opacity-0 group-hover:opacity-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $v['label'] }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ (float)$v['price_adjustment'] > 0 ? '+₱' . number_format((float)$v['price_adjustment'], 2) : ((float)$v['price_adjustment'] < 0 ? '₱' . number_format((float)$v['price_adjustment'], 2) : 'Base price') }}
                                            · {{ $v['initial_stock'] }} {{ $cat_stock_unit }}
                                            @if($cat_has_ar_support && !empty($v['ar_model_url'] ?? null))
                                                · <span class="font-medium text-purple-600 dark:text-purple-400">{{ __('AR') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <button type="button" wire:click="removeVariant({{ $i }})"
                                    class="ml-1 shrink-0 text-zinc-400 hover:text-red-500 dark:hover:text-red-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>{{-- end scrollable body --}}

    {{-- ── Footer ─────────────────────────────────────────────────────── --}}
    <div class="shrink-0 border-t border-zinc-200 px-5 py-4 dark:border-zinc-700">
        @if($mode === 'add' && $step === 1)
            <div class="flex items-center justify-between gap-2">
                <button type="button" wire:click="closePanel"
                    class="inline-flex h-9 items-center rounded-md border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="nextStep"
                    wire:loading.attr="disabled" wire:target="nextStep"
                    class="inline-flex h-9 items-center gap-2 rounded-md bg-sky-600 px-4 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="nextStep">{{ __('Next: Add variants →') }}</span>
                    <span wire:loading wire:target="nextStep" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </span>
                </button>
            </div>

        @elseif($mode === 'add' && $step === 2)
            <div class="flex items-center justify-between gap-2">
                <button type="button" wire:click="prevStep"
                    class="inline-flex h-9 items-center gap-1 rounded-md border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                    ← {{ __('Back') }}
                </button>
                <button type="button" wire:click="save"
                    wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex h-9 items-center gap-2 rounded-md bg-sky-600 px-4 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">{{ __('Save product') }}</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('Saving…') }}
                    </span>
                </button>
            </div>

        @else
            {{-- Edit mode: single step --}}
            <div class="flex items-center justify-between gap-2">
                <button type="button" wire:click="deleteProduct"
                    wire:loading.attr="disabled" wire:target="deleteProduct"
                    wire:confirm="{{ __('Are you sure you want to delete this product? This cannot be undone.') }}"
                    class="inline-flex h-9 items-center rounded-md border border-red-300 bg-white px-4 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-950/30">
                    {{ __('Delete product') }}
                </button>
                <div class="flex gap-2">
                    <button type="button" wire:click="closePanel"
                        class="inline-flex h-9 items-center rounded-md border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="save"
                        wire:loading.attr="disabled" wire:target="save"
                        class="inline-flex h-9 items-center gap-2 rounded-md bg-sky-600 px-4 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
                        <span wire:loading wire:target="save" class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            {{ __('Saving…') }}
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>{{-- end slide-over --}}

</div>{{-- end single root --}}
