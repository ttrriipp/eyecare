<x-layouts::app :title="__('New product')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
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
            <div class="space-y-4 lg:col-span-2">
                <form
                    method="POST"
                    action="{{ route('products.store') }}"
                    enctype="multipart/form-data"
                    id="product-create-form"
                    class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Name') }}
                            </label>
                            <flux:input
                                id="name"
                                name="name"
                                :label="false"
                                value="{{ old('name') }}"
                                required
                            />
                            @error('name')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="brand" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Brand') }}
                            </label>
                            <flux:input
                                id="brand"
                                name="brand"
                                :label="false"
                                value="{{ old('brand') }}"
                            />
                            @error('brand')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="sku" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('SKU') }}
                            </label>
                            <flux:input
                                id="sku"
                                name="sku"
                                :label="false"
                                value="{{ old('sku') }}"
                                required
                            />
                            @error('sku')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="price" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Price') }}
                            </label>
                            <flux:input
                                id="price"
                                name="price"
                                type="number"
                                step="0.01"
                                min="0"
                                :label="false"
                                value="{{ old('price') }}"
                                required
                            />
                            @error('price')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5 md:col-span-2">
                            <label for="category_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Category') }}
                            </label>
                            <select
                                id="category_id"
                                name="category_id"
                                required
                                class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="" disabled @selected(! old('category_id'))>{{ __('Select a category') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5 md:col-span-2">
                            <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Status') }}
                            </span>
                            <div
                                class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 shadow-sm dark:border-zinc-600 dark:bg-zinc-800/40 dark:shadow-none"
                            >
                                <label class="inline-flex cursor-pointer items-start gap-3 text-sm text-zinc-800 dark:text-zinc-200">
                                    <input type="hidden" name="is_active" value="0">
                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        @checked(filter_var(old('is_active', '1'), FILTER_VALIDATE_BOOLEAN))
                                        class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border border-zinc-400 bg-white accent-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:border-zinc-500 dark:bg-zinc-900 dark:accent-sky-500 dark:focus:ring-offset-zinc-900"
                                    >
                                    <span>{{ __('Active (visible to customers)') }}</span>
                                </label>
                            </div>
                            @error('is_active')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="lens_type" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Lens type') }}
                            </label>
                            <flux:input
                                id="lens_type"
                                name="lens_type"
                                :label="false"
                                value="{{ old('lens_type') }}"
                            />
                            @error('lens_type')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="frame_material" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Frame material') }}
                            </label>
                            <flux:input
                                id="frame_material"
                                name="frame_material"
                                :label="false"
                                value="{{ old('frame_material') }}"
                            />
                            @error('frame_material')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5 md:col-span-2">
                            <label for="ar_model_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('AR model URL (optional)') }}
                            </label>
                            <flux:input
                                id="ar_model_url"
                                name="ar_model_url"
                                :label="false"
                                value="{{ old('ar_model_url') }}"
                            />
                            @error('ar_model_url')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Description') }}
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-600"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <flux:button :href="route('products.index')" variant="ghost" wire:navigate>
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ __('Create product') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <h2 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Primary image') }}
                    </h2>

                    <div
                        class="aspect-[4/3] w-full overflow-hidden rounded-lg border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50"
                    >
                        <img
                            id="image-preview"
                            src=""
                            alt=""
                            class="hidden h-full w-full object-cover"
                        >
                        <div
                            id="image-placeholder"
                            class="flex h-full w-full items-center justify-center px-4 text-center text-xs text-zinc-500 dark:text-zinc-500"
                        >
                            {{ __('Optional — upload a photo for the catalog.') }}
                        </div>
                    </div>

                    <div class="mt-4 space-y-1.5">
                        <label for="image" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Image file') }}
                        </label>
                        <input
                            id="image"
                            name="image"
                            type="file"
                            accept="image/*"
                            form="product-create-form"
                            class="block w-full text-sm text-zinc-700 file:mr-4 file:rounded-md file:border-0 file:bg-sky-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-sky-800 hover:file:bg-sky-100 dark:text-zinc-300 dark:file:bg-sky-950 dark:file:text-sky-200 dark:hover:file:bg-sky-900/80"
                            onchange="window.productImagePreview && window.productImagePreview(event)"
                        >
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                            {{ __('JPG or PNG, up to 4 MB.') }}
                        </p>
                        @error('image')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.productImagePreview = function (event) {
            const input = event.target;
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('image-placeholder');

            if (!input.files || !input.files[0]) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                if (placeholder) {
                    placeholder.classList.add('hidden');
                }
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
            };
            reader.readAsDataURL(input.files[0]);
        };
    </script>
</x-layouts::app>
