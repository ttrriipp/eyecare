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

                            {{-- Name --}}
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

                            {{-- SKU --}}
                            <div class="space-y-1.5">
                                <label for="sku" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('SKU') }}
                                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                                </label>
                                <flux:input
                                    id="sku"
                                    name="sku"
                                    :label="false"
                                    value="{{ old('sku') }}"
                                    placeholder="e.g. FRM-001"
                                    required
                                />
                                @error('sku')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Brand --}}
                            <div class="space-y-1.5">
                                <label for="brand" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Brand') }}
                                    <span class="ml-0.5 text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
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

                            {{-- Price --}}
                            <div class="space-y-1.5">
                                <label for="price" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Price (₱)') }}
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

                            {{-- Category --}}
                            <div class="space-y-1.5">
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
                                    <option value="" disabled @selected(! old('category_id'))>{{ __('Select a category…') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
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
                                    <span class="ml-0.5 text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
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

                        </div>
                    </div>

                    {{-- ── Optical specifications ────────────────────────── --}}
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                        <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            {{ __('Optical specifications') }}
                        </h2>
                        <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-500">
                            {{ __('Leave blank if not applicable for this product type.') }}
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2">

                            {{-- Lens type --}}
                            <div class="space-y-1.5">
                                <label for="lens_type" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Lens type') }}
                                </label>
                                <flux:select
                                    id="lens_type"
                                    name="lens_type"
                                    :label="false"
                                >
                                    <option value="" @selected(! old('lens_type'))>{{ __('— None —') }}</option>
                                    @foreach(\App\Enums\LensType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected(old('lens_type') === $type->value)>
                                            {{ $type->label() }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                @error('lens_type')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Frame material --}}
                            <div class="space-y-1.5">
                                <label for="frame_material" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Frame material') }}
                                </label>
                                <flux:select
                                    id="frame_material"
                                    name="frame_material"
                                    :label="false"
                                >
                                    <option value="" @selected(! old('frame_material'))>{{ __('— None —') }}</option>
                                    @foreach(\App\Enums\FrameMaterial::cases() as $material)
                                        <option value="{{ $material->value }}" @selected(old('frame_material') === $material->value)>
                                            {{ $material->label() }}
                                        </option>
                                    @endforeach
                                </flux:select>
                                @error('frame_material')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- AR model URL --}}
                            <div class="space-y-1.5 sm:col-span-2">
                                <label for="ar_model_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('AR model URL') }}
                                    <span class="ml-0.5 text-xs font-normal text-zinc-400">({{ __('optional') }})</span>
                                </label>
                                <flux:input
                                    id="ar_model_url"
                                    name="ar_model_url"
                                    type="url"
                                    :label="false"
                                    value="{{ old('ar_model_url') }}"
                                    placeholder="https://example.com/models/frame.glb"
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
                                @checked(filter_var(old('is_active', '1'), FILTER_VALIDATE_BOOLEAN))
                                class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border border-zinc-400 bg-white accent-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:border-zinc-500 dark:bg-zinc-900 dark:accent-sky-500 dark:focus:ring-offset-zinc-900"
                            >
                            <span>
                                {{ __('Active — visible to customers') }}
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ __('Uncheck to save as a draft while you finish setting up the product.') }}
                                </span>
                            </span>
                        </label>

                        @error('is_active')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
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
