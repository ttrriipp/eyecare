<x-layouts::app :title="__('Adjust stock')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Adjust inventory') }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ $product->name }}
                    @if($product->sku)
                        <span class="text-zinc-400 dark:text-zinc-500">· {{ $product->sku }}</span>
                    @endif
                </flux:text>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('inventory.index')" wire:navigate>
                {{ __('Back to inventory') }}
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

                <div class="space-y-1.5">
                    <label for="quantity" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Quantity on hand') }}
                    </label>
                    <flux:input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="0"
                        :label="false"
                        value="{{ old('quantity', $inventory->quantity) }}"
                        required
                    />
                    @error('quantity')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="reorder_level" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Reorder level') }}
                    </label>
                    <flux:input
                        id="reorder_level"
                        name="reorder_level"
                        type="number"
                        min="0"
                        :label="false"
                        value="{{ old('reorder_level', $inventory->reorder_level) }}"
                        required
                    />
                    <p class="text-xs text-zinc-500 dark:text-zinc-500">
                        {{ __('When quantity is at or below this value, the item is flagged as low stock.') }}
                    </p>
                    @error('reorder_level')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="notes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Notes (optional)') }}
                    </label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-600"
                    >{{ old('notes', $inventory->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button :href="route('inventory.index')" variant="ghost" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save stock') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
