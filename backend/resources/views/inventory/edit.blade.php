<x-layouts::app :title="__('Adjust stock')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Adjust stock') }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ $product->name }}
                    <span class="text-zinc-400 dark:text-zinc-500">
                        · {{ __('Variant') }} {{ $variant->sku }}
                    </span>
                </flux:text>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('products.show', $product)" wire:navigate>
                {{ __('Back to product') }}
            </flux:button>
        </div>

        <div class="mx-auto w-full max-w-lg">
            <form
                method="POST"
                action="{{ route('inventory.update', $product) }}"
                class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                @csrf
                @method('PUT')

                <input type="hidden" name="product_variant_id" value="{{ old('product_variant_id', $variant->id) }}">
                @error('product_variant_id')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-600 dark:bg-zinc-800/50">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Quantity on hand') }}
                    </p>
                    <p class="mt-0.5 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                        {{ number_format($inventory->quantity) }}
                        @if($product->category?->stock_unit)
                            <span class="text-base font-normal text-zinc-500">{{ $product->category->stock_unit }}</span>
                        @endif
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label for="adjustment_type" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Type') }}
                        <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select
                        id="adjustment_type"
                        name="adjustment_type"
                        required
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="add" @selected(old('adjustment_type', 'add') === 'add')>{{ __('Add to stock') }}</option>
                        <option value="subtract" @selected(old('adjustment_type') === 'subtract')>{{ __('Remove from stock') }}</option>
                    </select>
                    @error('adjustment_type')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="quantity" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Quantity') }}
                        <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                    </label>
                    <flux:input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="1"
                        step="1"
                        :label="false"
                        value="{{ old('quantity') }}"
                        required
                        placeholder="{{ __('Units to add or remove') }}"
                    />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('How many units to add or remove (not the new total).') }}
                    </p>
                    @error('quantity')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="reason" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Reason') }}
                        <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                    </label>
                    <flux:input
                        id="reason"
                        name="reason"
                        :label="false"
                        value="{{ old('reason') }}"
                        required
                        placeholder="{{ __('e.g. received shipment, damaged goods, stock count') }}"
                    />
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="notes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Notes') }}
                        <span class="text-zinc-400 dark:text-zinc-500">({{ __('optional') }})</span>
                    </label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-600"
                        placeholder="{{ __('Any extra context for this adjustment') }}"
                    >{{ old('notes', $inventory->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button :href="route('products.show', $product)" variant="ghost" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Apply adjustment') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
