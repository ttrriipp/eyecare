<x-layouts::app :title="__('Edit :name', ['name' => $product->name])">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="!text-[#111827]">
                    {{ __('Edit product') }}
                </flux:heading>
                <flux:text class="!text-[#111827]">
                    {{ __('Update product details and images for the catalog and Android app.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-2">
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('products.show', $product) }}" wire:navigate>
                    {{ __('Back to product') }}
                </flux:button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                <form
                    method="POST"
                    action="{{ route('products.update', $product) }}"
                    enctype="multipart/form-data"
                    class="space-y-6 rounded-xl border border-neutral-200 bg-white p-6 shadow-[0_18px_45px_rgba(0,0,0,0.12)]"
                    id="product-form"
                >
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="name" class="block text-sm font-medium">
                                {{ __('Name') }}
                            </label>
                            <flux:input
                                id="name"
                                name="name"
                                :label="false"
                                value="{{ old('name', $product->name) }}"
                                required
                            />
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="brand" class="block text-sm font-medium">
                                {{ __('Brand') }}
                            </label>
                            <flux:input
                                id="brand"
                                name="brand"
                                :label="false"
                                value="{{ old('brand', $product->brand) }}"
                            />
                            @error('brand')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="sku" class="block text-sm font-medium">
                                {{ __('SKU') }}
                            </label>
                            <flux:input
                                id="sku"
                                name="sku"
                                :label="false"
                                value="{{ old('sku', $product->sku) }}"
                            />
                            @error('sku')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="price" class="block text-sm font-medium">
                                {{ __('Price') }}
                            </label>
                            <flux:input
                                id="price"
                                name="price"
                                type="number"
                                step="0.01"
                                min="0"
                                :label="false"
                                value="{{ old('price', $product->price) }}"
                                required
                            />
                            @error('price')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="category_id" class="block text-sm font-medium">
                                {{ __('Category') }}
                            </label>
                            <select
                                id="category_id"
                                name="category_id"
                                class="block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="">{{ __('Uncategorized') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium">
                                {{ __('Status') }}
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-neutral-700">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    @checked(old('is_active', $product->is_active ?? true))
                                    class="h-4 w-4 rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span>{{ __('Active (visible to customers)') }}</span>
                            </label>
                            @error('is_active')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="lens_type" class="block text-sm font-medium">
                                {{ __('Lens type') }}
                            </label>
                            <flux:input
                                id="lens_type"
                                name="lens_type"
                                :label="false"
                                value="{{ old('lens_type', $product->lens_type) }}"
                            />
                            @error('lens_type')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="frame_material" class="block text-sm font-medium">
                                {{ __('Frame material') }}
                            </label>
                            <flux:input
                                id="frame_material"
                                name="frame_material"
                                :label="false"
                                value="{{ old('frame_material', $product->frame_material) }}"
                            />
                            @error('frame_material')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 space-y-1.5">
                            <label for="ar_model_url" class="block text-sm font-medium">
                                {{ __('AR model URL (optional)') }}
                            </label>
                            <flux:input
                                id="ar_model_url"
                                name="ar_model_url"
                                :label="false"
                                value="{{ old('ar_model_url', $product->ar_model_url) }}"
                            />
                            @error('ar_model_url')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="description" class="block text-sm font-medium">
                            {{ __('Description') }}
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="block w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        >{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <flux:button type="submit" variant="primary">
                            {{ __('Save changes') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="space-y-4">
                <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-[0_18px_45px_rgba(0,0,0,0.12)]">
                    <h2 class="mb-3 text-sm font-semibold text-neutral-900">
                        {{ __('Primary image') }}
                    </h2>

                    @php
                        $primaryImage = $product->images->first();
                    @endphp

                    <div class="aspect-[4/3] w-full overflow-hidden rounded-lg border border-dashed border-neutral-300 bg-neutral-50">
                        <img
                            id="image-preview"
                            src="{{ $primaryImage?->image_url }}"
                            alt="{{ $product->name }}"
                            class="h-full w-full object-cover @if(! $primaryImage) hidden @endif"
                        >
                        @unless($primaryImage)
                            <div
                                id="image-placeholder"
                                class="flex h-full w-full items-center justify-center text-xs text-neutral-400"
                            >
                                {{ __('No image yet. Upload one below.') }}
                            </div>
                        @endunless
                    </div>

                    <div class="mt-4 space-y-3">
                        <div class="space-y-1.5">
                            <label for="image" class="block text-sm font-medium text-[#111827]">
                                {{ __('Upload new image') }}
                            </label>
                            <input
                                id="image"
                                name="image"
                                type="file"
                                accept="image/*"
                                form="product-form"
                                class="block w-full text-sm text-neutral-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                                onchange="window.productImagePreview && window.productImagePreview(event)"
                            >
                            <p class="mt-1 text-xs text-neutral-500">
                                {{ __('JPG or PNG, up to 4 MB.') }}
                            </p>
                            @error('image')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($primaryImage)
                            <div class="flex items-center justify-between gap-3">
                                <label class="inline-flex items-center gap-2 text-xs text-neutral-700">
                                    <input
                                        type="checkbox"
                                        name="remove_image"
                                        value="1"
                                        form="product-form"
                                        class="h-4 w-4 rounded border-neutral-300 text-red-600 focus:ring-red-500"
                                    >
                                    <span>{{ __('Remove current image') }}</span>
                                </label>
                            </div>
                        @endif
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

