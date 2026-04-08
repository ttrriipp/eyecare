<x-layouts::app :title="__('New product')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

        {{-- Page header --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Add product') }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Create a catalog item for the shop and mobile app.') }}
                </flux:text>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('products.index')" wire:navigate>
                {{ __('Back to products') }}
            </flux:button>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ── Main form ──────────────────────────────────────────────── --}}
            <div class="space-y-5 lg:col-span-2">
                <form
                    method="POST"
                    action="{{ route('products.store') }}"
                    enctype="multipart/form-data"
                    id="product-create-form"
                    class="space-y-6"
                >
                    @csrf

                    {{-- ── Core details ──────────────────────────────────── --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Core details') }}
                        </h2>

                        <div class="grid gap-4 sm:grid-cols-2">

                            {{-- Name + SKU note (full width) --}}
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
                                    placeholder="e.g. Classic Full-Rim Frame"
                                    required
                                    autofocus
                                />
                                @error('name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Brand --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="brand" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Brand') }}
                                </label>
                                <flux:input
                                    id="brand"
                                    name="brand"
                                    :label="false"
                                    value="{{ old('brand') }}"
                                    placeholder="e.g. Bolon"
                                />
                                @error('brand')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>


                            {{-- Category (full width for long labels) --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="category_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Category') }}
                                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                                </label>
                                <flux:select
                                    id="category_id"
                                    name="category_id"
                                    required
                                    :label="false"
                                >
                                    <option value="" disabled data-has-ar="0" @selected(! old('category_id'))>{{ __('Select a category…') }}</option>
                                    @foreach($categories as $category)
                                        <option
                                            value="{{ $category->id }}"
                                            data-has-ar="{{ $category->has_ar_support ? '1' : '0' }}"
                                            data-requires-expiry="{{ $category->requires_expiry_tracking ? '1' : '0' }}"
                                            @selected(old('category_id') == $category->id)
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
                                >{{ old('description') }}</textarea>
                                @error('description')
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
                                            @selected(old('supplier_id') == $supplier->id)
                                        >
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                @error('supplier_id')
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
                                    value="{{ old('price') }}"
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
                                    value="{{ old('cost_per_unit') }}"
                                    placeholder="0.00"
                                />
                                @error('cost_per_unit')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ── Initial stock (Inventory) ────────────────────── --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Initial stock') }}
                        </h2>
                        <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-500">
                            {{ __('These values are saved to Inventory when the product is created.') }}
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label for="inventory_quantity" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('On hand quantity') }}
                                </label>
                                <flux:input
                                    id="inventory_quantity"
                                    name="inventory_quantity"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :label="false"
                                    value="{{ old('inventory_quantity', 0) }}"
                                    placeholder="0"
                                />
                                @error('inventory_quantity')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="inventory_reorder_level" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Reorder level') }}
                                </label>
                                <flux:input
                                    id="inventory_reorder_level"
                                    name="inventory_reorder_level"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :label="false"
                                    value="{{ old('inventory_reorder_level', 0) }}"
                                    placeholder="0"
                                />
                                @error('inventory_reorder_level')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="inventory_notes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Inventory notes') }}
                                </label>
                                <textarea
                                    id="inventory_notes"
                                    name="inventory_notes"
                                    rows="3"
                                    placeholder="{{ __('Optional note for stock setup...') }}"
                                    class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                >{{ old('inventory_notes') }}</textarea>
                                @error('inventory_notes')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="inventory_reorder_quantity" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Reorder quantity') }}
                                </label>
                                <flux:input
                                    id="inventory_reorder_quantity"
                                    name="inventory_reorder_quantity"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :label="false"
                                    value="{{ old('inventory_reorder_quantity', 0) }}"
                                    placeholder="0"
                                />
                                @error('inventory_reorder_quantity')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-1.5">
                                <label for="inventory_batch_number" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Batch / lot number') }}
                                </label>
                                <flux:input
                                    id="inventory_batch_number"
                                    name="inventory_batch_number"
                                    :label="false"
                                    value="{{ old('inventory_batch_number') }}"
                                    placeholder="e.g. LOT-2026-04-A"
                                />
                                @error('inventory_batch_number')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="inventory-expires-at-wrapper" class="hidden space-y-1.5 sm:col-span-2">
                                <label id="inventory-expires-at-label" for="inventory_expires_at" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Expiration date') }}
                                </label>
                                <flux:input
                                    id="inventory_expires_at"
                                    name="inventory_expires_at"
                                    type="date"
                                    :label="false"
                                    value="{{ old('inventory_expires_at') }}"
                                />
                                @error('inventory_expires_at')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p id="inventory-expires-at-help" class="text-xs text-zinc-500 dark:text-zinc-500 hidden">
                                    {{ __('Required for the selected category.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- ── Actions ───────────────────────────────────────── --}}
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

            {{-- ── Sidebar: image upload ──────────────────────────────────── --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <h2 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Primary image') }}
                    </h2>

                    {{-- Preview / drop zone --}}
                    <label
                        id="image-drop-zone"
                        for="image"
                        class="group relative flex aspect-[4/3] w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 transition hover:border-sky-400 hover:bg-sky-50/40 dark:border-zinc-600 dark:bg-zinc-800/50 dark:hover:border-sky-500 dark:hover:bg-sky-900/10"
                    >
                        {{-- Actual preview --}}
                        <img
                            id="image-preview"
                            src=""
                            alt=""
                            class="absolute inset-0 hidden h-full w-full object-cover"
                        >

                        {{-- Placeholder overlay --}}
                        <div id="image-placeholder" class="relative flex flex-col items-center gap-2 px-4 text-center">
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

                        {{-- Hidden "change image" badge shown after selection --}}
                        <span id="image-change-badge" class="absolute bottom-2 right-2 hidden rounded-md bg-black/60 px-2 py-1 text-xs text-white backdrop-blur-sm">
                            {{ __('Change') }}
                        </span>
                    </label>

                    {{-- Actual file input (visually hidden, triggered by label) --}}
                    <input
                        id="image"
                        name="image"
                        type="file"
                        accept="image/*"
                        form="product-create-form"
                        class="sr-only"
                        onchange="productImagePreview(event)"
                    >

                    @error('image')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    {{-- Clear selection button (shown after file is picked) --}}
                    <button
                        id="image-clear-btn"
                        type="button"
                        class="mt-3 hidden w-full rounded-md border border-zinc-200 bg-white py-1.5 text-center text-xs text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-zinc-200"
                        onclick="productImageClear()"
                    >
                        {{ __('Remove selection') }}
                    </button>
                </div>

            </div>

        </div>
    </div>

    <script>
        (function () {
            const categorySelect = document.getElementById('category_id');
            const expiresInput = document.getElementById('inventory_expires_at');
            const expiresWrapper = document.getElementById('inventory-expires-at-wrapper');
            const expiresLabel = document.getElementById('inventory-expires-at-label');
            const expiresHelp = document.getElementById('inventory-expires-at-help');

            if (!categorySelect || !expiresInput || !expiresWrapper || !expiresLabel || !expiresHelp) return;

            function syncExpiryRequirement() {
                const opt = categorySelect.selectedOptions[0];
                const required = opt && opt.getAttribute('data-requires-expiry') === '1';

                expiresWrapper.classList.toggle('hidden', !required);
                expiresInput.required = !!required;
                expiresHelp.classList.toggle('hidden', !required);

                if (required) {
                    expiresLabel.innerHTML = "{{ __('Expiration date') }} <span class=\"ml-0.5 text-red-500\" aria-hidden=\"true\">*</span>";
                } else {
                    expiresLabel.textContent = "{{ __('Expiration date') }}";
                    expiresInput.value = '';
                }
            }

            categorySelect.addEventListener('change', syncExpiryRequirement);
            syncExpiryRequirement();
        })();

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

        // Native drag-and-drop support
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

                // Assign the file to the input via DataTransfer API
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        })();
    </script>
</x-layouts::app>
