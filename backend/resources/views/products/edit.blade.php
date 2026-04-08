<x-layouts::app :title="__('Edit :name', ['name' => $product->name])">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

        {{-- Page header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Edit product') }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Update product details and images for the catalog and Android app.') }}
                </flux:text>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('products.show', $product)" wire:navigate>
                {{ __('Back to product') }}
            </flux:button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ── Main form ──────────────────────────────────────────────── --}}
            <div class="space-y-5 lg:col-span-2">
                <form
                    method="POST"
                    action="{{ route('products.update', $product) }}"
                    enctype="multipart/form-data"
                    id="product-form"
                    class="space-y-6"
                >
                    @csrf
                    @method('PUT')

                    {{-- ── Core details ──────────────────────────────────── --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Core details') }}
                        </h2>

                        <div class="grid gap-4 sm:grid-cols-2">

                            {{-- Name (full width) --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Product name') }}
                                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                                </label>
                                <flux:input
                                    id="name"
                                    name="name"
                                    :label="false"
                                    value="{{ old('name', $product->name) }}"
                                    placeholder="e.g. Classic Full-Rim Frame"
                                    required
                                    autofocus
                                />
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- SKU (read-only strip, full width) --}}
                            <div class="sm:col-span-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2.5 dark:border-zinc-600 dark:bg-zinc-800/50">
                                <span class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('SKU') }}</span>
                                <p class="mt-0.5 font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $product->sku }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">{{ __('Stored on the default variant; assigned automatically and not editable here.') }}</p>
                            </div>

                            {{-- Brand --}}
                            <div class="space-y-1.5">
                                <label for="brand" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Brand') }}
                                </label>
                                <flux:input
                                    id="brand"
                                    name="brand"
                                    :label="false"
                                    value="{{ old('brand', $product->brand) }}"
                                    placeholder="e.g. Bolon"
                                />
                                @error('brand')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="supplier_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Supplier') }}
                                </label>
                                <flux:select
                                    id="supplier_id"
                                    name="supplier_id"
                                    :label="false"
                                >
                                    <option value="">{{ __('No supplier selected') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option
                                            value="{{ $supplier->id }}"
                                            @selected(old('supplier_id', $product->supplier_id) == $supplier->id)
                                        >
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                @error('supplier_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Category (full width) --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="category_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Category') }}
                                </label>
                                @if(auth()->user()?->isAdmin())
                                    <div class="mb-1">
                                        <a href="{{ route('products.categories.index') }}" class="text-xs text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">
                                            {{ __('Manage categories') }}
                                        </a>
                                    </div>
                                @endif
                                <flux:select
                                    id="category_id"
                                    name="category_id"
                                    :label="false"
                                >
                                    <option value="" data-has-ar="0">{{ __('Uncategorized') }}</option>
                                    @foreach($categories as $category)
                                        <option
                                            value="{{ $category->id }}"
                                            data-has-ar="{{ $category->has_ar_support ? '1' : '0' }}"
                                            data-requires-expiry="{{ $category->requires_expiry_tracking ? '1' : '0' }}"
                                            @selected(old('category_id', $product->category_id) == $category->id)
                                        >
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                @error('category_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Description') }}
                                </label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="4"
                                    placeholder="{{ __('Describe the product — materials, fit, use case, coatings…') }}"
                                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                >{{ old('description', $product->description) }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ── Pricing ───────────────────────────────────────── --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Pricing') }}
                        </h2>
                        <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-500">
                            {{ __('Set the product pricing and cost details.') }}
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label for="price" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Selling price (₱)') }}
                                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                                </label>
                                <flux:input
                                    id="price"
                                    name="price"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    :label="false"
                                    value="{{ old('price', $product->price) }}"
                                    placeholder="0.00"
                                    required
                                />
                                @error('price')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="cost_per_unit" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Cost per unit (₱)') }}
                                </label>
                                <flux:input
                                    id="cost_per_unit"
                                    name="cost_per_unit"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :label="false"
                                    value="{{ old('cost_per_unit', $product->cost_per_unit) }}"
                                    placeholder="0.00"
                                />
                                @error('cost_per_unit')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ── Virtual try-on (AR) — only for categories with AR support ─ --}}
                    @php
                        $selectedCategoryIdEdit = (int) old('category_id', $product->category_id);
                        $showArPanelEdit = $selectedCategoryIdEdit
                            ? (bool) ($categories->firstWhere('id', $selectedCategoryIdEdit)?->has_ar_support)
                            : false;
                    @endphp
                    <div
                        id="product-ar-section"
                        class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none {{ $showArPanelEdit ? '' : 'hidden' }}"
                    >
                        <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Virtual try-on (AR)') }}
                        </h2>
                        <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-500">
                            {{ __('Lens type, material, and other variant options are stored per product variant.') }}
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2">

                            {{-- AR model URL --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="ar_model_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('AR model URL') }}
                                </label>
                                <flux:input
                                    id="ar_model_url"
                                    name="ar_model_url"
                                    type="url"
                                    :label="false"
                                    value="{{ old('ar_model_url', $product->ar_model_url) }}"
                                    placeholder="https://example.com/models/frame.glb"
                                    @disabled(! $showArPanelEdit)
                                />
                                <p class="text-xs text-zinc-500 dark:text-zinc-500">
                                    {{ __('A publicly accessible .glb / .usdz file used for the try-on feature in the mobile app.') }}
                                </p>
                                @error('ar_model_url')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- ── Visibility ────────────────────────────────────── --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Visibility') }}
                        </h2>

                        <label class="inline-flex cursor-pointer items-start gap-3 text-sm text-zinc-800 dark:text-zinc-200">
                            <input type="hidden" name="is_active" value="0">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(filter_var(old('is_active', $product->is_active ? '1' : '0'), FILTER_VALIDATE_BOOLEAN))
                                class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border border-zinc-400 bg-white accent-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:border-zinc-500 dark:bg-zinc-900 dark:accent-sky-500 dark:focus:ring-offset-zinc-900"
                            >
                            <span>
                                {{ __('Active — visible to customers') }}
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ __('Uncheck to hide this product without deleting it.') }}
                                </span>
                            </span>
                        </label>

                        @error('is_active')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ── Actions ───────────────────────────────────────── --}}
                    <div class="flex items-center justify-end gap-3">
                        <flux:button type="submit" variant="primary" icon="check">
                            {{ __('Save changes') }}
                        </flux:button>
                    </div>

                </form>

                {{-- ── Danger zone ────────────────────────────────────────── --}}
                <div class="rounded-xl border border-red-200 bg-red-50/80 p-6 dark:border-red-900/60 dark:bg-red-950/30">
                    <h2 class="text-sm font-semibold text-red-900 dark:text-red-200">
                        {{ __('Danger zone') }}
                    </h2>
                    <p class="mt-1 text-sm text-red-800/90 dark:text-red-300/90">
                        {{ __('Removing a product hides it from the catalog. This can be undone from the database if needed.') }}
                    </p>
                    <form
                        method="POST"
                        action="{{ route('products.destroy', $product) }}"
                        class="mt-4"
                        onsubmit="return confirm(@json(__('Remove this product from the catalog?')))"
                    >
                        @csrf
                        @method('DELETE')
                        <flux:button type="submit" variant="danger" icon="trash">
                            {{ __('Remove product') }}
                        </flux:button>
                    </form>
                </div>
            </div>

            {{-- ── Sidebar: image upload ──────────────────────────────────── --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <h2 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Primary image') }}
                    </h2>

                    @php $primaryImage = $product->images->first(); @endphp

                    {{-- Preview / drop zone --}}
                    <label
                        id="image-drop-zone"
                        for="image"
                        class="group relative flex aspect-[4/3] w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 transition hover:border-sky-400 hover:bg-sky-50/40 dark:border-zinc-600 dark:bg-zinc-800/50 dark:hover:border-sky-500 dark:hover:bg-sky-900/10"
                    >
                        <img
                            id="image-preview"
                            src="{{ $primaryImage?->image_url }}"
                            alt="{{ $product->name }}"
                            class="absolute inset-0 h-full w-full object-cover @if(! $primaryImage) hidden @endif"
                        >

                        <div id="image-placeholder" class="relative flex flex-col items-center gap-2 px-4 text-center @if($primaryImage) hidden @endif">
                            <div class="flex size-10 items-center justify-center rounded-full bg-zinc-200 text-zinc-400 group-hover:bg-sky-100 group-hover:text-sky-500 dark:bg-zinc-700 dark:group-hover:bg-sky-900/40 dark:group-hover:text-sky-400 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                            </div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">
                                {{ __('Click or drag & drop') }}<br>
                                <span class="text-zinc-400 dark:text-zinc-500">JPG, PNG — up to 4 MB</span>
                            </span>
                        </div>

                        <span id="image-change-badge" class="absolute bottom-2 right-2 @if(! $primaryImage) hidden @endif rounded-md bg-black/60 px-2 py-1 text-xs text-white backdrop-blur-sm">
                            {{ __('Change') }}
                        </span>
                    </label>

                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept="image/*"
                        form="product-form"
                        class="sr-only"
                        onchange="productImagePreview(event)"
                    >

                    @error('image')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <button
                        id="image-clear-btn"
                        type="button"
                        class="mt-3 hidden w-full rounded-md border border-zinc-200 bg-white py-1.5 text-center text-xs text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-zinc-200"
                        onclick="productImageClear()"
                    >
                        {{ __('Remove selection') }}
                    </button>

                    {{-- Remove existing image checkbox --}}
                    @if($primaryImage)
                        <label class="mt-3 flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remove_image"
                                value="1"
                                form="product-form"
                                class="h-4 w-4 rounded border-zinc-400 text-red-600 focus:ring-red-500 dark:border-zinc-600 dark:bg-zinc-950"
                            >
                            <span>{{ __('Remove current image') }}</span>
                        </label>
                    @endif
                </div>

                {{-- Tips card --}}
                <div class="rounded-xl border border-sky-100 bg-sky-50 p-4 dark:border-sky-900/40 dark:bg-sky-950/30">
                    <p class="text-xs font-semibold text-sky-800 dark:text-sky-300">{{ __('Tips') }}</p>
                    <ul class="mt-2 space-y-1 text-xs text-sky-700 dark:text-sky-400">
                        <li class="flex items-start gap-1.5">
                            <span class="mt-0.5 shrink-0">•</span>
                            {{ __('Use a square or 4:3 image for best results in the app.') }}
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="mt-0.5 shrink-0">•</span>
                            {{ __('Lens type and frame material are used to filter products in the mobile catalog.') }}
                        </li>
                        <li class="flex items-start gap-1.5">
                            <span class="mt-0.5 shrink-0">•</span>
                            {{ __('The AR URL enables the try-on feature. Leave blank if not supported.') }}
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <script>
        function productImagePreview(event) {
            const input = event.target;
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('image-placeholder');
            const badge = document.getElementById('image-change-badge');
            const clearBtn = document.getElementById('image-clear-btn');

            if (!input.files || !input.files[0]) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                if (placeholder) placeholder.classList.add('hidden');
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                if (badge) badge.classList.remove('hidden');
                if (clearBtn) clearBtn.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }

        function productImageClear() {
            const input = document.getElementById('image');
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('image-placeholder');
            const badge = document.getElementById('image-change-badge');
            const clearBtn = document.getElementById('image-clear-btn');

            if (input) input.value = '';
            if (preview) { preview.src = ''; preview.classList.add('hidden'); }
            if (placeholder) placeholder.classList.remove('hidden');
            if (badge) badge.classList.add('hidden');
            if (clearBtn) clearBtn.classList.add('hidden');
        }

        (function () {
            const categorySelect = document.getElementById('category_id');
            const arSection = document.getElementById('product-ar-section');
            const arInput = document.getElementById('ar_model_url');
            if (categorySelect && arSection) {
                function syncArSection(fromUserChange) {
                    const opt = categorySelect.selectedOptions[0];
                    const show = opt && opt.getAttribute('data-has-ar') === '1';
                    arSection.classList.toggle('hidden', !show);
                    if (arInput) {
                        arInput.disabled = !show;
                        if (!show && fromUserChange) {
                            arInput.value = '';
                        }
                    }
                }
                categorySelect.addEventListener('change', () => syncArSection(true));
                syncArSection(false);
            }
        })();

        (function () {
            const zone = document.getElementById('image-drop-zone');
            if (!zone) return;

            zone.addEventListener('dragover', e => {
                e.preventDefault();
                zone.classList.add('border-sky-400', 'bg-sky-50/40');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('border-sky-400', 'bg-sky-50/40');
            });

            zone.addEventListener('drop', e => {
                e.preventDefault();
                zone.classList.remove('border-sky-400', 'bg-sky-50/40');

                const file = e.dataTransfer?.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;

                const input = document.getElementById('image');
                if (!input) return;

                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        })();
    </script>
</x-layouts::app>
