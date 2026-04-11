@php
    $oldVariants = old('variants', [
        [
            'price' => '',
            'cost_per_unit' => '',
            'quantity' => 0,
            'reorder_level' => 5,
            'reorder_quantity' => 0,
            'color' => '',
            'frame_size' => '',
            'material' => '',
            'lens_type' => '',
            'power' => '',
            'duration' => '',
            'batch_number' => '',
            'expires_at' => '',
            'ar_model_url' => '',
        ],
    ]);
    $defaultVariantIndex = (int) old('default_variant_index', 0);

    $categoryFlags = $categories->mapWithKeys(fn ($c) => [
        $c->id => [
            'has_color' => (bool) $c->has_color,
            'has_frame_size' => (bool) $c->has_frame_size,
            'has_material' => (bool) $c->has_material,
            'has_lens_type' => (bool) $c->has_lens_type,
            'has_power_field' => (bool) $c->has_power_field,
            'has_duration' => (bool) $c->has_duration,
            'has_ar_support' => (bool) $c->has_ar_support,
            'requires_expiry_tracking' => (bool) $c->requires_expiry_tracking,
            'name' => $c->name,
        ],
    ]);
@endphp

<x-layouts::app :title="__('New product')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Add product') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Products'), 'href' => route('products.index')],
                        ['label' => __('New product')],
                    ]"
                />
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('products.index')" wire:navigate>
                {{ __('Back to products') }}
            </flux:button>
        </div>

                <form
                    method="POST"
                    action="{{ route('products.store') }}"
                    enctype="multipart/form-data"
                    id="product-create-form"
                    class="space-y-6"
                >
                    @csrf

            {{-- Product information --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                    {{ __('Product information') }}
                        </h2>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Product name') }}
                                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                                </label>
                                <flux:input
                                    id="name"
                                    name="name"
                                    :label="false"
                                    value="{{ old('name') }}"
                                    required
                                    autofocus
                                />
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="brand" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Brand') }}
                                </label>
                        <flux:input id="brand" name="brand" :label="false" value="{{ old('brand') }}" />
                                @error('brand')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="category_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Category') }}
                                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                                </label>
                        <select
                                    id="category_id"
                                    name="category_id"
                                    required
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                >
                            <option value="" disabled @selected(! old('category_id'))>{{ __('Select a category…') }}</option>
                                    @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                        </select>
                                @error('category_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Description') }}
                                </label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="4"
                                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                >{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                    <div class="sm:col-span-2">
                        <label class="inline-flex cursor-pointer items-start gap-3 text-sm text-zinc-800 dark:text-zinc-200">
                            <input type="hidden" name="is_active" value="0">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(filter_var(old('is_active', '1'), FILTER_VALIDATE_BOOLEAN))
                                class="mt-0.5 size-4 shrink-0 rounded border border-zinc-400 bg-white accent-sky-600 focus:ring-2 focus:ring-sky-500 dark:border-zinc-500 dark:bg-zinc-900"
                            >
                            <span>{{ __('Active — visible to customers') }}</span>
                        </label>
                        @error('is_active')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Variants table --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                        {{ __('Variants') }}
                    </h2>
                    <p id="variants-category-hint" class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Columns update based on the selected category.') }}
                    </p>
                </div>

                @error('variants')
                    <p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                @error('default_variant_index')
                    <p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-[80rem] w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-400">
                                <th class="px-2 py-2 whitespace-nowrap">{{ __('Default') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap">{{ __('Photos') }}</th>
                                <th class="variant-col px-2 py-2 whitespace-nowrap hidden" data-cat-field="color">{{ __('Color') }}</th>
                                <th class="variant-col px-2 py-2 whitespace-nowrap hidden" data-cat-field="frame_size">{{ __('Frame size') }}</th>
                                <th class="variant-col px-2 py-2 whitespace-nowrap hidden" data-cat-field="material">{{ __('Material') }}</th>
                                <th class="variant-col px-2 py-2 min-w-[8rem] hidden" data-cat-field="lens_type">{{ __('Lens type') }}</th>
                                <th class="variant-col px-2 py-2 whitespace-nowrap hidden" data-cat-field="power">{{ __('Power') }}</th>
                                <th class="variant-col px-2 py-2 whitespace-nowrap hidden" data-cat-field="duration">{{ __('Duration') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap">{{ __('Price (₱)') }} <span class="text-red-500">*</span></th>
                                <th class="px-2 py-2 whitespace-nowrap">{{ __('Cost (₱)') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap">{{ __('Qty') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap">{{ __('Reorder') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap">{{ __('Reorder qty') }}</th>
                                <th class="variant-col px-2 py-2 whitespace-nowrap hidden" data-cat-field="batch_number">{{ __('Batch') }}</th>
                                <th class="variant-col px-2 py-2 whitespace-nowrap hidden" data-cat-field="expires_at">{{ __('Expires') }}</th>
                                <th class="variant-col px-2 py-2 min-w-[10rem] hidden" data-cat-field="ar_model_url">{{ __('AR URL') }}</th>
                                <th class="px-2 py-2 whitespace-nowrap"></th>
                            </tr>
                        </thead>
                        <tbody id="variant-rows">
                            @foreach($oldVariants as $idx => $row)
                                <tr class="variant-row border-b border-zinc-100 dark:border-zinc-800" data-variant-index="{{ $idx }}">
                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="radio"
                                            name="default_variant_index"
                                            value="{{ $idx }}"
                                            @checked($defaultVariantIndex === $idx)
                                            class="size-4 border-zinc-400 text-sky-600 focus:ring-sky-500"
                                        >
                                    </td>
                                    @include('products.partials.variant-images-cell', ['idx' => $idx, 'existingImages' => []])
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="color">
                                        <input type="text" name="variants[{{ $idx }}][color]" value="{{ $row['color'] ?? '' }}" maxlength="60" class="w-28 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.color') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="frame_size">
                                        <select name="variants[{{ $idx }}][frame_size]" class="w-36 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                            <option value="">{{ __('Select…') }}</option>
                                            <option value="Small (50mm)" @selected(($row['frame_size'] ?? '') === 'Small (50mm)')>Small (50mm)</option>
                                            <option value="Medium (54mm)" @selected(($row['frame_size'] ?? '') === 'Medium (54mm)')>Medium (54mm)</option>
                                            <option value="Large (56mm)" @selected(($row['frame_size'] ?? '') === 'Large (56mm)')>Large (56mm)</option>
                                            <option value="XL (58mm)" @selected(($row['frame_size'] ?? '') === 'XL (58mm)')>XL (58mm)</option>
                                        </select>
                                        @error('variants.'.$idx.'.frame_size') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="material">
                                        <select name="variants[{{ $idx }}][material]" class="w-32 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                            <option value="">{{ __('Select…') }}</option>
                                            <option value="Acetate" @selected(($row['material'] ?? '') === 'Acetate')>Acetate</option>
                                            <option value="Titanium" @selected(($row['material'] ?? '') === 'Titanium')>Titanium</option>
                                            <option value="Metal" @selected(($row['material'] ?? '') === 'Metal')>Metal</option>
                                            <option value="TR-90" @selected(($row['material'] ?? '') === 'TR-90')>TR-90</option>
                                            <option value="Polycarbonate" @selected(($row['material'] ?? '') === 'Polycarbonate')>Polycarbonate</option>
                                            <option value="High-index" @selected(($row['material'] ?? '') === 'High-index')>High-index</option>
                                            <option value="CR-39 Plastic" @selected(($row['material'] ?? '') === 'CR-39 Plastic')>CR-39 Plastic</option>
                                            <option value="Stainless Steel" @selected(($row['material'] ?? '') === 'Stainless Steel')>Stainless Steel</option>
                                        </select>
                                        @error('variants.'.$idx.'.material') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="lens_type">
                                        <select name="variants[{{ $idx }}][lens_type]" class="min-w-[10rem] rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                            <option value="">{{ __('Select…') }}</option>
                                            <optgroup label="{{ __('Sunglasses') }}">
                                                <option value="Classic tint" @selected(($row['lens_type'] ?? '') === 'Classic tint')>Classic tint</option>
                                                <option value="Polarized" @selected(($row['lens_type'] ?? '') === 'Polarized')>Polarized</option>
                                                <option value="Mirrored" @selected(($row['lens_type'] ?? '') === 'Mirrored')>Mirrored</option>
                                                <option value="Gradient" @selected(($row['lens_type'] ?? '') === 'Gradient')>Gradient</option>
                                            </optgroup>
                                            <optgroup label="{{ __('Contacts') }}">
                                                <option value="Daily" @selected(($row['lens_type'] ?? '') === 'Daily')>Daily</option>
                                                <option value="Bi-weekly" @selected(($row['lens_type'] ?? '') === 'Bi-weekly')>Bi-weekly</option>
                                                <option value="Monthly" @selected(($row['lens_type'] ?? '') === 'Monthly')>Monthly</option>
                                                <option value="Quarterly" @selected(($row['lens_type'] ?? '') === 'Quarterly')>Quarterly</option>
                                            </optgroup>
                                            <optgroup label="{{ __('Rx lenses') }}">
                                                <option value="Single Vision" @selected(($row['lens_type'] ?? '') === 'Single Vision')>Single Vision</option>
                                                <option value="Bifocal" @selected(($row['lens_type'] ?? '') === 'Bifocal')>Bifocal</option>
                                                <option value="Progressive" @selected(($row['lens_type'] ?? '') === 'Progressive')>Progressive</option>
                                                <option value="Reading" @selected(($row['lens_type'] ?? '') === 'Reading')>Reading</option>
                                            </optgroup>
                                            <optgroup label="{{ __('General') }}">
                                                <option value="Clear" @selected(($row['lens_type'] ?? '') === 'Clear')>Clear</option>
                                                <option value="Blue light filter" @selected(($row['lens_type'] ?? '') === 'Blue light filter')>Blue light filter</option>
                                                <option value="Photochromic" @selected(($row['lens_type'] ?? '') === 'Photochromic')>Photochromic</option>
                                                <option value="Anti-radiation" @selected(($row['lens_type'] ?? '') === 'Anti-radiation')>Anti-radiation</option>
                                            </optgroup>
                                        </select>
                                        @error('variants.'.$idx.'.lens_type') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="power">
                                        <input type="text" name="variants[{{ $idx }}][power]" value="{{ $row['power'] ?? '' }}" maxlength="40" placeholder="-2.00" class="w-24 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.power') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="duration">
                                        <select name="variants[{{ $idx }}][duration]" class="w-32 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                            <option value="">{{ __('Select…') }}</option>
                                            @foreach (['Daily', 'Bi-weekly', 'Monthly', 'Quarterly', 'Yearly'] as $dur)
                                                <option value="{{ $dur }}" @selected(($row['duration'] ?? '') === $dur)>{{ $dur }}</option>
                                            @endforeach
                                        </select>
                                        @error('variants.'.$idx.'.duration') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input type="number" name="variants[{{ $idx }}][price]" value="{{ $row['price'] ?? '' }}" step="0.01" min="0.01" required class="w-24 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.price') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input type="number" name="variants[{{ $idx }}][cost_per_unit]" value="{{ $row['cost_per_unit'] ?? '' }}" step="0.01" min="0" class="w-24 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.cost_per_unit') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input type="number" name="variants[{{ $idx }}][quantity]" value="{{ $row['quantity'] ?? 0 }}" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.quantity') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input type="number" name="variants[{{ $idx }}][reorder_level]" value="{{ $row['reorder_level'] ?? 5 }}" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.reorder_level') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input type="number" name="variants[{{ $idx }}][reorder_quantity]" value="{{ $row['reorder_quantity'] ?? 0 }}" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.reorder_quantity') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="batch_number">
                                        <input type="text" name="variants[{{ $idx }}][batch_number]" value="{{ $row['batch_number'] ?? '' }}" maxlength="120" class="w-28 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.batch_number') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="expires_at">
                                        <input type="date" name="variants[{{ $idx }}][expires_at]" value="{{ $row['expires_at'] ?? '' }}" class="w-36 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.expires_at') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="ar_model_url">
                                        <input type="url" name="variants[{{ $idx }}][ar_model_url]" value="{{ $row['ar_model_url'] ?? '' }}" placeholder="https://…" class="w-44 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.ar_model_url') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <button type="button" class="variant-remove text-xs text-red-600 hover:underline disabled:opacity-40" {{ count($oldVariants) <= 1 ? 'disabled' : '' }}>
                                            {{ __('Remove') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" id="variant-add-row" class="rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        {{ __('Add variant row') }}
                    </button>
                </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <flux:button :href="route('products.index')" variant="ghost" wire:navigate>
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary" icon="plus">
                            {{ __('Create product') }}
                        </flux:button>
                    </div>
                </form>
            </div>

    <template id="variant-row-template">
        <tr class="variant-row border-b border-zinc-100 dark:border-zinc-800" data-variant-index="__INDEX__">
            <td class="px-2 py-2 align-middle">
                <input type="radio" name="default_variant_index" value="__INDEX__" class="size-4 border-zinc-400 text-sky-600 focus:ring-sky-500">
            </td>
            <td class="variant-images-cell px-2 py-2 align-top min-w-[12rem] max-w-[15rem]">
                <label class="flex cursor-pointer flex-col gap-1 rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-2.5 py-2 transition hover:border-sky-400 hover:bg-sky-50/60 dark:border-zinc-600 dark:bg-zinc-900/50 dark:hover:border-sky-500 dark:hover:bg-sky-950/30">
                    <span class="text-xs font-semibold text-sky-700 dark:text-sky-400">{{ __('Add photos') }}</span>
                    <span class="text-[10px] leading-snug text-zinc-500 dark:text-zinc-400">{{ __('Choose several files at once.') }}</span>
                    <input type="file" name="variants[__INDEX__][images][]" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="sr-only variant-images-input">
                    </label>
                <div class="variant-images-preview mt-2 flex flex-wrap gap-1" aria-live="polite"></div>
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="color">
                <input type="text" name="variants[__INDEX__][color]" maxlength="60" class="w-28 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="frame_size">
                <select name="variants[__INDEX__][frame_size]" class="w-36 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="">{{ __('Select…') }}</option>
                    <option value="Small (50mm)">Small (50mm)</option>
                    <option value="Medium (54mm)">Medium (54mm)</option>
                    <option value="Large (56mm)">Large (56mm)</option>
                    <option value="XL (58mm)">XL (58mm)</option>
                </select>
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="material">
                <select name="variants[__INDEX__][material]" class="w-32 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
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
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="lens_type">
                <select name="variants[__INDEX__][lens_type]" class="min-w-[10rem] rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="">{{ __('Select…') }}</option>
                    <optgroup label="{{ __('Sunglasses') }}">
                        <option value="Classic tint">Classic tint</option>
                        <option value="Polarized">Polarized</option>
                        <option value="Mirrored">Mirrored</option>
                        <option value="Gradient">Gradient</option>
                    </optgroup>
                    <optgroup label="{{ __('Contacts') }}">
                        <option value="Daily">Daily</option>
                        <option value="Bi-weekly">Bi-weekly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                    </optgroup>
                    <optgroup label="{{ __('Rx lenses') }}">
                        <option value="Single Vision">Single Vision</option>
                        <option value="Bifocal">Bifocal</option>
                        <option value="Progressive">Progressive</option>
                        <option value="Reading">Reading</option>
                    </optgroup>
                    <optgroup label="{{ __('General') }}">
                        <option value="Clear">Clear</option>
                        <option value="Blue light filter">Blue light filter</option>
                        <option value="Photochromic">Photochromic</option>
                        <option value="Anti-radiation">Anti-radiation</option>
                    </optgroup>
                </select>
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="power">
                <input type="text" name="variants[__INDEX__][power]" maxlength="40" placeholder="-2.00" class="w-24 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="duration">
                <select name="variants[__INDEX__][duration]" class="w-32 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="">{{ __('Select…') }}</option>
                    @foreach (['Daily', 'Bi-weekly', 'Monthly', 'Quarterly', 'Yearly'] as $dur)
                        <option value="{{ $dur }}">{{ $dur }}</option>
                    @endforeach
                </select>
            </td>
            <td class="px-2 py-2 align-middle">
                <input type="number" name="variants[__INDEX__][price]" step="0.01" min="0.01" required class="w-24 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="px-2 py-2 align-middle">
                <input type="number" name="variants[__INDEX__][cost_per_unit]" step="0.01" min="0" class="w-24 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="px-2 py-2 align-middle">
                <input type="number" name="variants[__INDEX__][quantity]" value="0" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="px-2 py-2 align-middle">
                <input type="number" name="variants[__INDEX__][reorder_level]" value="5" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="px-2 py-2 align-middle">
                <input type="number" name="variants[__INDEX__][reorder_quantity]" value="0" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="batch_number">
                <input type="text" name="variants[__INDEX__][batch_number]" maxlength="120" class="w-28 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="expires_at">
                <input type="date" name="variants[__INDEX__][expires_at]" class="w-36 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="ar_model_url">
                <input type="url" name="variants[__INDEX__][ar_model_url]" placeholder="https://…" class="w-44 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="px-2 py-2 align-middle">
                <button type="button" class="variant-remove text-xs text-red-600 hover:underline">{{ __('Remove') }}</button>
            </td>
        </tr>
    </template>

    <script type="application/json" id="category-flags-json">@json($categoryFlags)</script>

    <script>
        (function () {
            const categorySelect = document.getElementById('category_id');
            const flagsEl = document.getElementById('category-flags-json');
            const tbody = document.getElementById('variant-rows');
            const template = document.getElementById('variant-row-template');
            const addBtn = document.getElementById('variant-add-row');

            if (!categorySelect || !flagsEl || !tbody || !template) return;

            const categoryFlags = JSON.parse(flagsEl.textContent || '{}');

            const FIELD_KEYS = ['color', 'frame_size', 'material', 'lens_type', 'power', 'duration', 'batch_number', 'expires_at', 'ar_model_url'];

            function flagsForSelectedCategory() {
                const id = categorySelect.value;
                return categoryFlags[id] || null;
            }

            function applyColumnVisibility() {
                const f = flagsForSelectedCategory();
                document.querySelectorAll('.variant-col[data-cat-field]').forEach(function (el) {
                    const key = el.getAttribute('data-cat-field');
                    if (!f) {
                        el.classList.add('hidden');
                        return;
                    }
                    const map = {
                        color: f.has_color,
                        frame_size: f.has_frame_size,
                        material: f.has_material,
                        lens_type: f.has_lens_type,
                        power: f.has_power_field,
                        duration: f.has_duration,
                        batch_number: f.requires_expiry_tracking,
                        expires_at: f.requires_expiry_tracking,
                        ar_model_url: f.has_ar_support,
                    };
                    el.classList.toggle('hidden', !map[key]);
                });
            }

            function reindexVariantRows() {
                const rows = tbody.querySelectorAll('tr.variant-row');
                rows.forEach(function (row, i) {
                    row.dataset.variantIndex = String(i);
                    row.querySelectorAll('input, select').forEach(function (input) {
                        const name = input.getAttribute('name');
                        if (name && name.indexOf('variants[') === 0) {
                            input.setAttribute('name', name.replace(/variants\[\d+]/, 'variants[' + i + ']'));
                        }
                        if (input.type === 'radio' && input.name === 'default_variant_index') {
                            input.value = String(i);
                        }
                    });
                });
                updateRemoveButtons();
            }

            function updateRemoveButtons() {
                const rows = tbody.querySelectorAll('tr.variant-row');
                rows.forEach(function (row) {
                    const btn = row.querySelector('.variant-remove');
                    if (btn) btn.disabled = rows.length <= 1;
                });
            }

            function addRow() {
                const rows = tbody.querySelectorAll('tr.variant-row');
                const idx = rows.length;
                const html = template.innerHTML.replace(/__INDEX__/g, String(idx));
                tbody.insertAdjacentHTML('beforeend', html);
                const newRow = tbody.lastElementChild;
                if (newRow) {
                    const radios = tbody.querySelectorAll('input[name="default_variant_index"]');
                    if (radios.length === 1) {
                        radios[0].checked = true;
                    }
                }
                reindexVariantRows();
                applyColumnVisibility();
            }

            tbody.addEventListener('click', function (e) {
                const t = e.target;
                if (!t || !t.classList || !t.classList.contains('variant-remove')) return;
                const row = t.closest('tr.variant-row');
                if (!row || tbody.querySelectorAll('tr.variant-row').length <= 1) return;
                row.remove();
                reindexVariantRows();
                const firstRadio = tbody.querySelector('input[name="default_variant_index"]');
                if (firstRadio) firstRadio.checked = true;
            });

            if (addBtn) addBtn.addEventListener('click', addRow);

            tbody.addEventListener('change', function (e) {
                const input = e.target;
                if (!input || !input.classList || !input.classList.contains('variant-images-input')) return;
                const cell = input.closest('.variant-images-cell');
                if (!cell) return;
                const preview = cell.querySelector('.variant-images-preview');
                if (!preview) return;
                preview.innerHTML = '';
                if (!input.files || !input.files.length) return;
                const max = 12;
                for (let j = 0; j < Math.min(input.files.length, max); j++) {
                    const file = input.files[j];
                    if (!file.type.startsWith('image/')) continue;
                    const wrap = document.createElement('span');
                    wrap.className = 'relative h-10 w-10 shrink-0 overflow-hidden rounded border border-zinc-200 dark:border-zinc-600';
                    const img = document.createElement('img');
                    img.alt = '';
                    img.className = 'h-full w-full object-cover';
                    wrap.appendChild(img);
                    preview.appendChild(wrap);
                    const r = new FileReader();
                    r.onload = function (ev) {
                        img.src = ev.target.result;
                    };
                    r.readAsDataURL(file);
                }
            });

            categorySelect.addEventListener('change', applyColumnVisibility);
            applyColumnVisibility();
            reindexVariantRows();
        })();
    </script>
</x-layouts::app>
