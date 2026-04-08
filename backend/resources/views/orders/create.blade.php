@php
    $rawItems = old('items');
    if ($rawItems === null) {
        $rows = [
            ['product_variant_id' => '', 'quantity' => 1],
            ['product_variant_id' => '', 'quantity' => 1],
            ['product_variant_id' => '', 'quantity' => 1],
        ];
    } else {
        $rows = [];
        foreach (array_values($rawItems) as $row) {
            $rows[] = [
                'product_variant_id' => $row['product_variant_id'] ?? '',
                'quantity' => isset($row['quantity']) ? max(1, (int) $row['quantity']) : 1,
            ];
        }
        while (count($rows) < 3) {
            $rows[] = ['product_variant_id' => '', 'quantity' => 1];
        }
    }
    $nextLineIndex = count($rows);
    $defaultCustomerType = old('customer_type', $customers->isEmpty() ? 'walk_in' : 'registered');
    $showRegisteredFields = $customers->isNotEmpty() && $defaultCustomerType === 'registered';
@endphp

<x-layouts::app :title="__('Create order')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Create order') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Orders'), 'href' => route('orders.index')],
                        ['label' => __('Create order')],
                    ]"
                />
            </div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('orders.index')" wire:navigate>
                {{ __('Back to orders') }}
            </flux:button>
        </div>

        @if($products->isEmpty())
            <div
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-100"
                role="status"
            >
                {{ __('There are no active products. Add catalog items before creating orders.') }}
            </div>
        @else
            <form
                method="POST"
                action="{{ route('orders.store') }}"
                class="space-y-6"
                id="staff-order-form"
            >
                @csrf

                @if($errors->any())
                    <div
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-100"
                        role="alert"
                    >
                        <p class="font-medium">{{ __('Please fix the following:') }}</p>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($customers->isEmpty())
                    <div
                        class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100"
                        role="status"
                    >
                        {{ __('No registered customer accounts yet. Use walk-in and enter name and phone, or add customer accounts to attach orders to a profile.') }}
                    </div>
                @endif

                <div
                    class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <div class="space-y-4" id="customer-type-section">
                        <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Customer') }} <span class="text-red-600 dark:text-red-400">*</span>
                        </span>

                        @if($customers->isNotEmpty())
                            <div class="flex flex-wrap gap-6">
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                                    <input
                                        type="radio"
                                        name="customer_type"
                                        value="registered"
                                        class="h-4 w-4 border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950"
                                        @checked($defaultCustomerType === 'registered')
                                    >
                                    <span>{{ __('Registered customer') }}</span>
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                                    <input
                                        type="radio"
                                        name="customer_type"
                                        value="walk_in"
                                        class="h-4 w-4 border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950"
                                        @checked($defaultCustomerType === 'walk_in')
                                    >
                                    <span>{{ __('Walk-in') }}</span>
                                </label>
                            </div>
                        @else
                            <input type="hidden" name="customer_type" value="walk_in">
                        @endif

                        <div
                            id="registered-customer-fields"
                            class="space-y-1.5 @if(! $showRegisteredFields) hidden @endif"
                        >
                            <label for="user_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Account') }}
                            </label>
                            <select
                                id="user_id"
                                name="user_id"
                                class="block w-full max-w-xl rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                <option value="">{{ __('Select a registered customer…') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('user_id') == $customer->id)>
                                        {{ $customer->name }}
                                        @if($customer->email)
                                            — {{ $customer->email }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div
                            id="walk-in-customer-fields"
                            class="space-y-3 @if($showRegisteredFields) hidden @endif"
                        >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label for="walk_in_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ __('Walk-in name') }} <span class="text-red-600 dark:text-red-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="walk_in_name"
                                        name="walk_in_name"
                                        value="{{ old('walk_in_name') }}"
                                        autocomplete="name"
                                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    >
                                    @error('walk_in_name')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label for="walk_in_phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ __('Phone') }} <span class="text-red-600 dark:text-red-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="walk_in_phone"
                                        name="walk_in_phone"
                                        value="{{ old('walk_in_phone') }}"
                                        autocomplete="tel"
                                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm tabular-nums text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    >
                                    @error('walk_in_phone')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('customer_type')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="space-y-3">
                        <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Line items') }} <span class="text-red-600 dark:text-red-400">*</span>
                        </span>

                        <div
                            class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                        >
                            <div
                                class="hidden border-b border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-400 sm:grid sm:grid-cols-[1fr_7.5rem_2.75rem] sm:items-center sm:gap-2"
                            >
                                <div class="text-start">{{ __('Product') }}</div>
                                <div class="text-center">{{ __('Qty') }}</div>
                                <div class="text-center">
                                    <span class="sr-only">{{ __('Actions') }}</span>
                                </div>
                            </div>
                            <div
                                id="order-lines"
                                class="divide-y divide-zinc-200 dark:divide-zinc-700"
                                data-next-index="{{ $nextLineIndex }}"
                            >
                                @foreach($rows as $i => $row)
                                    <div
                                        class="order-line grid grid-cols-1 items-center gap-3 px-3 py-3 sm:grid-cols-[1fr_7.5rem_2.75rem] sm:items-center sm:gap-2"
                                        data-order-line
                                    >
                                        <div class="min-w-0">
                                            <label class="mb-1 block text-xs text-zinc-500 sm:hidden">
                                                {{ __('Product') }}
                                            </label>
                                            <select
                                                name="items[{{ $i }}][product_variant_id]"
                                                class="block w-full min-w-0 rounded-md border border-zinc-300 bg-white px-2 py-2 text-sm text-zinc-900 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                            >
                                                <option value="">{{ __('—') }}</option>
                                                @foreach($products as $product)
                                                    @continue(!$product->defaultVariant)
                                                    <option
                                                        value="{{ $product->defaultVariant->id }}"
                                                        @selected((string) ($row['product_variant_id'] ?? '') === (string) $product->defaultVariant->id)
                                                    >
                                                        {{ $product->name }}
                                                        @if($product->sku)
                                                            ({{ $product->sku }})
                                                        @endif
                                                        — {{ \App\Support\Money::peso($product->price) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div
                                            class="qty-control flex items-center justify-center gap-1 sm:w-[7.5rem] sm:shrink-0"
                                        >
                                            <label class="mr-auto text-xs text-zinc-500 sm:hidden">
                                                {{ __('Qty') }}
                                            </label>
                                            <button
                                                type="button"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-zinc-100 text-lg font-medium leading-none text-zinc-800 hover:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                                                data-qty-adjust="dec"
                                                aria-label="{{ __('Decrease quantity') }}"
                                            >
                                                −
                                            </button>
                                            <input
                                                type="number"
                                                name="items[{{ $i }}][quantity]"
                                                value="{{ (int) ($row['quantity'] ?? 1) }}"
                                                min="1"
                                                step="1"
                                                inputmode="numeric"
                                                class="h-9 w-12 shrink-0 rounded-md border border-zinc-300 bg-white px-1 text-center text-sm tabular-nums text-zinc-900 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                            />
                                            <button
                                                type="button"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-zinc-100 text-lg font-medium leading-none text-zinc-800 hover:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                                                data-qty-adjust="inc"
                                                aria-label="{{ __('Increase quantity') }}"
                                            >
                                                +
                                            </button>
                                        </div>
                                        <div class="flex justify-end sm:justify-center">
                                            <button
                                                type="button"
                                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-white text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                                data-remove-order-line
                                                title="{{ __('Remove line') }}"
                                                aria-label="{{ __('Remove line') }}"
                                            >
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <flux:button type="button" variant="outline" icon="plus" id="add-order-line">
                                {{ __('Add line') }}
                            </flux:button>
                        </div>

                        @error('items')
                            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="discount_amount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Discount amount') }}
                        </label>
                        <flux:input
                            id="discount_amount"
                            name="discount_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            :label="false"
                            value="{{ old('discount_amount', '0') }}"
                            placeholder="{{ __('Optional manual discount (e.g. SC/PWD)') }}"
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Recorded manually and deducted from the order total.') }}
                        </p>
                        @error('discount_amount')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="notes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Notes') }}
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            placeholder="{{ __('Optional notes for staff…') }}"
                            class="block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-600"
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ __('Create order') }}
                    </flux:button>
                    <flux:button variant="ghost" :href="route('orders.index')" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </form>

            <template id="order-line-template">
                <div
                    class="order-line grid grid-cols-1 items-center gap-3 px-3 py-3 sm:grid-cols-[1fr_7.5rem_2.75rem] sm:items-center sm:gap-2"
                    data-order-line
                >
                    <div class="min-w-0">
                        <label class="mb-1 block text-xs text-zinc-500 sm:hidden">{{ __('Product') }}</label>
                        <select
                            name="items[__INDEX__][product_variant_id]"
                            class="block w-full min-w-0 rounded-md border border-zinc-300 bg-white px-2 py-2 text-sm text-zinc-900 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            <option value="">{{ __('—') }}</option>
                            @foreach($products as $product)
                                @continue(!$product->defaultVariant)
                                <option value="{{ $product->defaultVariant->id }}">
                                    {{ $product->name }}
                                    @if($product->sku)
                                        ({{ $product->sku }})
                                    @endif
                                    — {{ \App\Support\Money::peso($product->price) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div
                        class="qty-control flex items-center justify-center gap-1 sm:w-[7.5rem] sm:shrink-0"
                    >
                        <label class="mr-auto text-xs text-zinc-500 sm:hidden">{{ __('Qty') }}</label>
                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-zinc-100 text-lg font-medium leading-none text-zinc-800 hover:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                            data-qty-adjust="dec"
                            aria-label="{{ __('Decrease quantity') }}"
                        >
                            −
                        </button>
                        <input
                            type="number"
                            name="items[__INDEX__][quantity]"
                            value="1"
                            min="1"
                            step="1"
                            inputmode="numeric"
                            class="h-9 w-12 shrink-0 rounded-md border border-zinc-300 bg-white px-1 text-center text-sm tabular-nums text-zinc-900 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                        />
                        <button
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-zinc-100 text-lg font-medium leading-none text-zinc-800 hover:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"
                            data-qty-adjust="inc"
                            aria-label="{{ __('Increase quantity') }}"
                        >
                            +
                        </button>
                    </div>
                    <div class="flex justify-end sm:justify-center">
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-zinc-300 bg-white text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                            data-remove-order-line
                            title="{{ __('Remove line') }}"
                            aria-label="{{ __('Remove line') }}"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            <script>
                (function () {
                    const container = document.getElementById('order-lines');
                    const template = document.getElementById('order-line-template');
                    const addBtn = document.getElementById('add-order-line');
                    if (!container || !template || !addBtn) return;

                    const maxLines = 40;

                    function reindexOrderLines() {
                        const lines = container.querySelectorAll('[data-order-line]');
                        lines.forEach(function (line, index) {
                            line.querySelectorAll('select[name^="items["], input[name^="items["]').forEach(function (el) {
                                const name = el.getAttribute('name');
                                if (name && name.indexOf('items[') === 0) {
                                    el.setAttribute('name', name.replace(/items\[\d+\]/, 'items[' + index + ']'));
                                }
                            });
                        });
                        container.setAttribute('data-next-index', String(lines.length));

                        const disableRemove = lines.length <= 1;
                        lines.forEach(function (line) {
                            const rm = line.querySelector('[data-remove-order-line]');
                            if (rm) {
                                rm.disabled = disableRemove;
                                rm.classList.toggle('opacity-40', disableRemove);
                                rm.classList.toggle('pointer-events-none', disableRemove);
                            }
                        });
                    }

                    addBtn.addEventListener('click', function () {
                        const current = container.querySelectorAll('[data-order-line]').length;
                        if (current >= maxLines) return;

                        const idx = current;
                        const html = template.innerHTML.replace(/__INDEX__/g, String(idx));
                        const wrap = document.createElement('div');
                        wrap.innerHTML = html.trim();
                        const node = wrap.firstElementChild;
                        if (!node) return;

                        container.appendChild(node);
                        reindexOrderLines();
                    });

                    container.addEventListener('click', function (e) {
                        const removeBtn = e.target.closest('[data-remove-order-line]');
                        if (removeBtn && container.contains(removeBtn) && !removeBtn.disabled) {
                            const line = removeBtn.closest('[data-order-line]');
                            if (!line) return;
                            if (container.querySelectorAll('[data-order-line]').length <= 1) return;
                            line.remove();
                            reindexOrderLines();
                            return;
                        }

                        const btn = e.target.closest('[data-qty-adjust]');
                        if (!btn || !container.contains(btn)) return;

                        const control = btn.closest('.qty-control');
                        if (!control) return;

                        const input = control.querySelector('input[type="number"]');
                        if (!input) return;

                        let v = parseInt(input.value, 10);
                        if (Number.isNaN(v) || v < 1) v = 1;

                        if (btn.getAttribute('data-qty-adjust') === 'inc') {
                            v += 1;
                        } else {
                            v = Math.max(1, v - 1);
                        }

                        input.value = String(v);
                    });

                    reindexOrderLines();
                })();

                (function () {
                    const regFields = document.getElementById('registered-customer-fields');
                    const walkFields = document.getElementById('walk-in-customer-fields');
                    if (!regFields || !walkFields) return;

                    const radios = document.querySelectorAll('#staff-order-form input[name="customer_type"]');

                    function syncCustomerFields() {
                        const type =
                            radios.length > 0
                                ? document.querySelector('#staff-order-form input[name="customer_type"]:checked')
                                      ?.value || 'walk_in'
                                : 'walk_in';
                        const showReg = type === 'registered';

                        regFields.classList.toggle('hidden', !showReg);
                        walkFields.classList.toggle('hidden', showReg);

                        regFields.querySelectorAll('select, input').forEach(function (el) {
                            el.disabled = !showReg;
                        });
                        walkFields.querySelectorAll('input').forEach(function (el) {
                            el.disabled = showReg;
                        });
                    }

                    radios.forEach(function (r) {
                        r.addEventListener('change', syncCustomerFields);
                    });
                    syncCustomerFields();
                })();
            </script>
        @endif
    </div>
</x-layouts::app>
