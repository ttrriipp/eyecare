@php
    if (old('variants')) {
        $oldVariants = old('variants');
        $defaultVariantIndex = (int) old('default_variant_index', 0);
    } else {
        $sortedVariants = $product->variants->sortBy('id')->values();
        $oldVariants = $sortedVariants->isEmpty()
            ? [[
                'id' => null,
                'sku' => '',
                'price' => '',
                'cost_per_unit' => '',
                'opening_quantity' => 0,
                'color' => '',
                'frame_size' => '',
                'material' => '',
                'lens_type' => '',
                'power' => '',
                'duration' => '',
                'ar_model_url' => '',
            ]]
            : $sortedVariants->map(function ($v) {
                return [
                    'id' => $v->id,
                    'sku' => $v->sku ?? '',
                    'price' => $v->price,
                    'cost_per_unit' => $v->cost_per_unit,
                    'opening_quantity' => 0,
                    'color' => $v->color ?? '',
                    'frame_size' => $v->frame_size ?? '',
                    'material' => $v->material ?? '',
                    'lens_type' => $v->lens_type ?? '',
                    'power' => $v->power ?? '',
                    'duration' => $v->duration ?? '',
                    'ar_model_url' => $v->ar_model_url ?? '',
                ];
            })->all();
        $defIdx = $sortedVariants->isEmpty() ? 0 : $sortedVariants->search(fn ($v) => $v->is_default);
        $defaultVariantIndex = (int) ($defIdx !== false ? $defIdx : 0);
    }

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

<x-layouts::app :title="__('Edit :name', ['name' => $product->name])">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Edit product') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Products'), 'href' => route('products.index')],
                        ['label' => $product->name, 'href' => route('products.show', $product)],
                        ['label' => __('Edit')],
                    ]"
                />
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('products.show', $product)" wire:navigate>
                {{ __('Back to product') }}
            </flux:button>
        </div>

        <form
            method="POST"
            action="{{ route('products.update', $product) }}"
            id="product-edit-form"
            class="space-y-6"
            data-original-category-id="{{ $product->category_id }}"
        >
            @csrf
            @method('PUT')

            @php
                $categoryChangedInForm = (string) old('category_id', (string) $product->category_id) !== (string) $product->category_id;
            @endphp

            <div class="rounded-xl border-2 border-sky-200 bg-white p-6 shadow-sm dark:border-sky-900/50 dark:bg-zinc-900 dark:shadow-none">
                <h2 class="mb-5 text-sm font-semibold uppercase tracking-wide text-sky-800 dark:text-sky-300">
                    {{ __('Product information') }}
                </h2>

                <div class="grid gap-5 lg:grid-cols-12 lg:items-start">
                    <div class="space-y-1.5 lg:col-span-7">
                        <span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ __('Category') }}
                            <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                        </span>
                        @if($categoryChangeLocked)
                            <input type="hidden" name="category_id" id="category_id" value="{{ old('category_id', $product->category_id) }}">
                            <p class="text-xs leading-snug text-zinc-500 dark:text-zinc-400">
                                {{ __('Read-only — sales or inventory history exists for a variant.') }}
                            </p>
                            <div
                                class="mt-1 rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-800 shadow-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200"
                                title="{{ __('Category cannot be changed after order or inventory history exists. Archive this product and create a new one if recategorization is needed.') }}"
                            >
                                {{ $product->category?->name ?? __('Uncategorized') }}
                            </div>
                        @else
                            <p class="text-xs leading-snug text-zinc-500 dark:text-zinc-400">{{ __('Drives which variant columns appear below.') }}</p>
                            <select
                                id="category_id"
                                name="category_id"
                                required
                                class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="" disabled @selected(! old('category_id', $product->category_id))>{{ __('Select a category…') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($categoryChangeRequiresDestructiveConfirm)
                                <div
                                    id="category-destructive-confirm"
                                    class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-950/30 {{ $categoryChangedInForm ? '' : 'hidden' }}"
                                >
                                    <label class="flex cursor-pointer items-start gap-2 text-sm text-amber-950 dark:text-amber-100">
                                        <input
                                            type="checkbox"
                                            name="confirm_destroy_variants_for_category"
                                            value="1"
                                            class="mt-0.5 size-4 shrink-0 rounded border-amber-400 text-amber-700 focus:ring-amber-500"
                                            @checked(old('confirm_destroy_variants_for_category'))
                                        >
                                        <span>
                                            {{ __('Changing the category will delete all existing variants. I understand existing SKUs, stock rows, and variant images for this product will be removed.') }}
                                        </span>
                                    </label>
                                    <p class="mt-2 text-xs text-amber-800/90 dark:text-amber-200/90">
                                        {{ __('You must check this box before saving if you select a different category.') }}
                                    </p>
                                </div>
                                @error('confirm_destroy_variants_for_category')
                                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            @endif
                        @endif
                        @error('category_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5 lg:col-span-5">
                        <label for="product_status" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ __('Status') }}
                        </label>
                        <p class="text-xs leading-snug text-zinc-500 dark:text-zinc-400">{{ __('Inactive products are hidden from the storefront.') }}</p>
                        <select
                            id="product_status"
                            name="is_active"
                            class="mt-1 block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            <option value="1" @selected((string) old('is_active', $product->is_active ? '1' : '0') !== '0')>{{ __('Active') }}</option>
                            <option value="0" @selected((string) old('is_active', $product->is_active ? '1' : '0') === '0')>{{ __('Inactive') }}</option>
                        </select>
                        @error('is_active')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($categoryChangeLocked)
                        <p class="rounded-lg border border-zinc-200 bg-zinc-50/80 px-3 py-2.5 text-xs leading-relaxed text-zinc-600 dark:border-zinc-600 dark:bg-zinc-900/50 dark:text-zinc-400 lg:col-span-12">
                            {{ __('Category cannot be changed after order or inventory history exists. Archive this product and create a new one if recategorization is needed.') }}
                        </p>
                    @endif
                </div>

                <div class="mt-6 grid gap-5 lg:grid-cols-12">
                    <div class="space-y-1.5 lg:col-span-7">
                        <label for="name" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ __('Product name') }}
                            <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                        </label>
                        <flux:input
                            id="name"
                            name="name"
                            :label="false"
                            value="{{ old('name', $product->name) }}"
                            required
                            autofocus
                        />
                        @error('name')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5 lg:col-span-5">
                        <label for="brand" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                            {{ __('Brand') }}
                        </label>
                        <flux:input id="brand" name="brand" :label="false" value="{{ old('brand', $product->brand) }}" />
                        @error('brand')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 space-y-1.5">
                    <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Description') }}
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                    >{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                        {{ __('Variants') }}
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Manage photos and stock on the product detail page.') }}</p>
                </div>

                <div
                    id="variant-category-notice"
                    class="mb-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-relaxed text-sky-950 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-100"
                    role="status"
                ></div>

                @error('variants')
                    <p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                @error('default_variant_index')
                    <p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="-mx-1 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-[64rem] w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-400">
                                <th class="px-2 py-2 align-bottom whitespace-nowrap" title="{{ __('The variant used as the main thumbnail and default price in listings.') }}">
                                    <span class="block">{{ __('Default') }}</span>
                                    <span class="mt-0.5 block max-w-[7rem] text-[10px] font-normal normal-case leading-snug text-zinc-400 dark:text-zinc-500">{{ __('Primary in listings') }}</span>
                                </th>
                                <th class="px-2 py-2 align-bottom whitespace-nowrap" title="{{ __('Override only if needed; format PRD- + 8 characters.') }}">
                                    <span class="block">{{ __('SKU') }}</span>
                                    <span class="mt-0.5 block max-w-[8rem] text-[10px] font-normal normal-case leading-snug text-zinc-400 dark:text-zinc-500">{{ __('PRD-XXXXXXXX') }}</span>
                                </th>
                                <th class="variant-col px-2 py-2 align-bottom whitespace-nowrap hidden" data-cat-field="color">{{ __('Color') }}</th>
                                <th class="variant-col px-2 py-2 align-bottom whitespace-nowrap hidden" data-cat-field="frame_size">{{ __('Frame size') }}</th>
                                <th class="variant-col px-2 py-2 align-bottom whitespace-nowrap hidden" data-cat-field="material">{{ __('Material') }}</th>
                                <th class="variant-col px-2 py-2 align-bottom min-w-[8rem] hidden" data-cat-field="lens_type">{{ __('Lens type') }}</th>
                                <th class="variant-col px-2 py-2 align-bottom whitespace-nowrap hidden" data-cat-field="power">{{ __('Power') }}</th>
                                <th class="variant-col px-2 py-2 align-bottom whitespace-nowrap hidden" data-cat-field="duration">{{ __('Duration') }}</th>
                                <th class="px-2 py-2 align-bottom whitespace-nowrap">{{ __('Price (₱)') }} <span class="text-red-500">*</span></th>
                                <th class="px-2 py-2 align-bottom whitespace-nowrap" title="{{ __('Your cost per unit (optional).') }}">
                                    <span class="block">{{ __('Cost (₱)') }}</span>
                                    <span class="mt-0.5 block text-[10px] font-normal normal-case text-zinc-400 dark:text-zinc-500">{{ __('Internal') }}</span>
                                </th>
                                <th class="px-2 py-2 align-bottom whitespace-nowrap" title="{{ __('For new rows only. Existing stock is managed on the Inventory tab.') }}">
                                    <span class="block">{{ __('Opening qty') }}</span>
                                    <span class="mt-0.5 block text-[10px] font-normal normal-case text-zinc-400 dark:text-zinc-500">{{ __('New rows') }}</span>
                                </th>
                                <th class="variant-col px-2 py-2 align-bottom min-w-[10rem] hidden" data-cat-field="ar_model_url">{{ __('AR URL') }}</th>
                                <th class="px-2 py-2 align-bottom whitespace-nowrap"></th>
                            </tr>
                        </thead>
                        <tbody id="variant-rows">
                            @foreach($oldVariants as $idx => $row)
                                <tr
                                    class="variant-row border-b border-zinc-100 dark:border-zinc-800"
                                    data-variant-index="{{ $idx }}"
                                    data-persisted-variant="{{ filled($row['id'] ?? null) ? '1' : '0' }}"
                                >
                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="radio"
                                            name="default_variant_index"
                                            value="{{ $idx }}"
                                            @checked($defaultVariantIndex === $idx)
                                            class="size-4 border-zinc-400 text-sky-600 focus:ring-sky-500"
                                            title="{{ __('Primary variant for listings') }}"
                                        >
                                        @if(filled($row['id'] ?? null))
                                            <input type="hidden" name="variants[{{ $idx }}][id]" value="{{ $row['id'] }}">
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <input
                                            type="text"
                                            name="variants[{{ $idx }}][sku]"
                                            value="{{ $row['sku'] ?? '' }}"
                                            maxlength="12"
                                            placeholder="{{ __('Auto') }}"
                                            class="sku-field w-28 rounded border border-dashed border-zinc-300 bg-zinc-50 px-2 py-1 font-mono text-xs uppercase dark:border-zinc-600 dark:bg-zinc-900"
                                        >
                                        @error('variants.'.$idx.'.sku') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
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
                                        @if(filled($row['id'] ?? null))
                                            <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>
                                        @else
                                            <input type="number" name="variants[{{ $idx }}][opening_quantity]" value="{{ (int) ($row['opening_quantity'] ?? 0) }}" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
                                            @error('variants.'.$idx.'.opening_quantity') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                        @endif
                                    </td>
                                    <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="ar_model_url">
                                        <input type="url" name="variants[{{ $idx }}][ar_model_url]" value="{{ $row['ar_model_url'] ?? '' }}" placeholder="https://…" class="w-44 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                                        @error('variants.'.$idx.'.ar_model_url') <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2 align-middle">
                                        <button
                                            type="button"
                                            class="variant-remove text-xs text-red-600 hover:underline disabled:cursor-not-allowed disabled:opacity-40 disabled:no-underline"
                                            @disabled(count($oldVariants) <= 1 || filled($row['id'] ?? null))
                                            @if(filled($row['id'] ?? null))
                                                title="{{ __('Saved variants cannot be removed here. Only rows you add on this page can be removed before saving.') }}"
                                            @elseif(count($oldVariants) <= 1)
                                                title="{{ __('At least one variant row is required.') }}"
                                            @endif
                                        >
                                            {{ __('Remove') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button
                        type="button"
                        id="variant-add-row"
                        class="text-sm font-medium text-zinc-600 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 dark:text-zinc-400 dark:decoration-zinc-600 dark:hover:text-zinc-100"
                    >
                        {{ __('+ Add variant row') }}
                    </button>
                </div>
            </div>

            <div class="flex flex-col-reverse items-stretch justify-between gap-3 sm:flex-row sm:items-center">
                <flux:button :href="route('products.show', $product)" variant="ghost" wire:navigate class="sm:mr-auto">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check" class="sm:ml-auto">
                    {{ __('Save changes') }}
                </flux:button>
            </div>
        </form>
    </div>

    <template id="variant-row-template">
        <tr class="variant-row border-b border-zinc-100 dark:border-zinc-800" data-variant-index="__INDEX__" data-persisted-variant="0">
            <td class="px-2 py-2 align-middle">
                <input type="radio" name="default_variant_index" value="__INDEX__" class="size-4 border-zinc-400 text-sky-600 focus:ring-sky-500" title="{{ __('Primary variant for listings') }}">
            </td>
            <td class="px-2 py-2 align-middle">
                <input type="text" name="variants[__INDEX__][sku]" value="" maxlength="12" placeholder="{{ __('Auto') }}" class="sku-field w-28 rounded border border-dashed border-zinc-300 bg-zinc-50 px-2 py-1 font-mono text-xs uppercase dark:border-zinc-600 dark:bg-zinc-900">
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
                <input type="number" name="variants[__INDEX__][opening_quantity]" value="0" min="0" step="1" class="w-20 rounded border border-zinc-300 px-2 py-1 text-xs tabular-nums dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="variant-col px-2 py-2 align-middle hidden" data-cat-field="ar_model_url">
                <input type="url" name="variants[__INDEX__][ar_model_url]" placeholder="https://…" class="w-44 rounded border border-zinc-300 px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800">
            </td>
            <td class="px-2 py-2 align-middle">
                <button type="button" class="variant-remove text-xs text-red-600 hover:underline disabled:cursor-not-allowed disabled:opacity-40 disabled:no-underline">{{ __('Remove') }}</button>
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
            const noticeEl = document.getElementById('variant-category-notice');

            if (!categorySelect || !flagsEl || !tbody || !template || !noticeEl) return;

            const form = document.getElementById('product-edit-form');
            const destructiveWrap = document.getElementById('category-destructive-confirm');
            if (destructiveWrap && form && categorySelect.tagName === 'SELECT') {
                const orig = form.getAttribute('data-original-category-id') ?? '';
                function syncDestructiveConfirm() {
                    const changed = String(categorySelect.value) !== String(orig);
                    destructiveWrap.classList.toggle('hidden', !changed);
                    if (!changed) {
                        const cb = destructiveWrap.querySelector('input[type="checkbox"]');
                        if (cb) {
                            cb.checked = false;
                        }
                    }
                }
                categorySelect.addEventListener('change', syncDestructiveConfirm);
                syncDestructiveConfirm();
            }

            const categoryFlags = JSON.parse(flagsEl.textContent || '{}');
            const removeTitlePersisted = @json(__('Saved variants cannot be removed here. Only rows you add on this page can be removed before saving.'));
            const removeTitleMinRows = @json(__('At least one variant row is required.'));

            function randomSku() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                let s = '';
                for (let i = 0; i < 8; i++) s += chars.charAt(Math.floor(Math.random() * chars.length));
                return 'PRD-' + s;
            }

            function flagsForSelectedCategory() {
                const id = categorySelect.value;
                return categoryFlags[id] || null;
            }

            function labelFor(key) {
                const map = {
                    color: @json(__('Color')),
                    frame_size: @json(__('Frame size')),
                    material: @json(__('Material')),
                    lens_type: @json(__('Lens type')),
                    power: @json(__('Power')),
                    duration: @json(__('Duration')),
                    ar_model_url: @json(__('AR model URL')),
                };
                return map[key] || key;
            }

            function buildCategoryNotice(f) {
                if (!f) {
                    return @json(__('Select a category to see which variant fields apply.'));
                }
                const active = [];
                const inactiveParts = [];
                const req = @json(__('required'));

                if (f.has_color) active.push(labelFor('color') + ' (' + req + ')'); else inactiveParts.push(labelFor('color'));
                if (f.has_frame_size) active.push(labelFor('frame_size') + ' (' + req + ')'); else inactiveParts.push(labelFor('frame_size'));
                if (f.has_material) active.push(labelFor('material') + ' (' + req + ')'); else inactiveParts.push(labelFor('material'));
                if (f.has_lens_type) active.push(labelFor('lens_type') + ' (' + req + ')'); else inactiveParts.push(labelFor('lens_type'));
                if (f.has_power_field) active.push(labelFor('power') + ' (' + req + ')'); else inactiveParts.push(labelFor('power'));
                if (f.has_duration) active.push(labelFor('duration') + ' (' + req + ')'); else inactiveParts.push(labelFor('duration'));
                if (f.has_ar_support) active.push(labelFor('ar_model_url') + ' (' + @json(__('optional')) + ')');

                let msg = @json(__('Category:')) + ' ' + f.name + '. ';
                if (active.length) {
                    msg += @json(__('Shown and required where marked:')) + ' ' + active.join(', ') + '. ';
                }
                if (inactiveParts.length) {
                    msg += @json(__('Not used for this category:')) + ' ' + inactiveParts.join(', ') + '. ';
                }
                if (f.requires_expiry_tracking) {
                    msg += @json(__('Batch and expiry are set after save on the Inventory tab.'));
                }
                return msg.trim();
            }

            function refreshSkuPlaceholders() {
                tbody.querySelectorAll('tr.variant-row .sku-field').forEach(function (input) {
                    if (!input.value || input.value.trim() === '') {
                        input.placeholder = randomSku();
                    }
                });
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
                        ar_model_url: f.has_ar_support,
                    };
                    el.classList.toggle('hidden', !map[key]);
                });
                noticeEl.textContent = buildCategoryNotice(f);
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
                refreshSkuPlaceholders();
            }

            function updateRemoveButtons() {
                const rows = tbody.querySelectorAll('tr.variant-row');
                const onlyOne = rows.length <= 1;
                rows.forEach(function (row) {
                    const btn = row.querySelector('.variant-remove');
                    if (!btn) return;
                    const persisted = row.getAttribute('data-persisted-variant') === '1';
                    btn.disabled = onlyOne || persisted;
                    if (persisted && !onlyOne) {
                        btn.title = removeTitlePersisted;
                    } else if (onlyOne) {
                        btn.title = removeTitleMinRows;
                    } else {
                        btn.removeAttribute('title');
                    }
                });
            }

            function addRow() {
                const rows = tbody.querySelectorAll('tr.variant-row');
                const idx = rows.length;
                const html = template.innerHTML.replace(/__INDEX__/g, String(idx));
                tbody.insertAdjacentHTML('beforeend', html);
                const radios = tbody.querySelectorAll('input[name="default_variant_index"]');
                if (radios.length === 1) {
                    radios[0].checked = true;
                }
                reindexVariantRows();
                applyColumnVisibility();
            }

            tbody.addEventListener('click', function (e) {
                const t = e.target;
                if (!t || !t.classList || !t.classList.contains('variant-remove')) return;
                const row = t.closest('tr.variant-row');
                if (!row || row.getAttribute('data-persisted-variant') === '1') return;
                if (tbody.querySelectorAll('tr.variant-row').length <= 1) return;
                row.remove();
                reindexVariantRows();
                const firstRadio = tbody.querySelector('input[name="default_variant_index"]');
                if (firstRadio) firstRadio.checked = true;
            });

            if (addBtn) addBtn.addEventListener('click', addRow);

            categorySelect.addEventListener('change', applyColumnVisibility);
            applyColumnVisibility();
            reindexVariantRows();
        })();

        (function () {
            if (window.__eyecareVariantImagePreviewInit) return;
            window.__eyecareVariantImagePreviewInit = true;

            function revokePreviewImages(wrap) {
                if (!wrap) return;
                wrap.querySelectorAll('img[data-preview-blob]').forEach(function (img) {
                    if (img.src && img.src.indexOf('blob:') === 0) {
                        URL.revokeObjectURL(img.src);
                    }
                });
            }

            document.addEventListener('change', function (e) {
                const input = e.target;
                if (!input || !input.classList || !input.classList.contains('variant-images-input')) return;
                const cell = input.closest('.variant-images-cell');
                if (!cell) return;
                const outer = cell.querySelector('.variant-images-preview-wrap');
                const wrap = cell.querySelector('.variant-images-preview');
                if (!wrap) return;

                revokePreviewImages(wrap);
                wrap.innerHTML = '';

                if (!input.files || !input.files.length) {
                    if (outer) outer.classList.add('hidden');
                    return;
                }

                const frag = document.createDocumentFragment();
                Array.prototype.forEach.call(input.files, function (file) {
                    if (!file.type.match(/^image\//)) return;
                    const url = URL.createObjectURL(file);
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = '';
                    img.setAttribute('data-preview-blob', '1');
                    img.className = 'h-14 w-14 shrink-0 rounded-md border border-zinc-200 object-cover dark:border-zinc-600';
                    frag.appendChild(img);
                });
                wrap.appendChild(frag);
                if (outer) outer.classList.toggle('hidden', wrap.children.length === 0);
            }, true);

            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form || form.tagName !== 'FORM') return;
                form.querySelectorAll('.variant-images-preview').forEach(revokePreviewImages);
            }, true);
        })();
    </script>
</x-layouts::app>
