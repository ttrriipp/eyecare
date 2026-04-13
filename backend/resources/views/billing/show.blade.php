@php
    $ps = $bill->payment_status;
    $terminal = in_array($ps, [\App\Enums\PaymentStatus::Voided, \App\Enums\PaymentStatus::Refunded], true);
@endphp

<x-layouts::app :title="$bill->invoice_number">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('orders.billing.index')" wire:navigate>
                    {{ __('Back to billing') }}
                </flux:button>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                        {{ $bill->invoice_number }}
                    </flux:heading>
                    <flux:button variant="ghost" size="sm" icon="arrow-top-right-on-square" :href="route('orders.billing.print', $bill)" target="_blank">
                        {{ __('Print / PDF') }}
                    </flux:button>
                </div>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Billing'), 'href' => route('orders.billing.index')],
                        ['label' => $bill->invoice_number],
                    ]"
                />
            </div>
            <div class="flex flex-col items-start gap-2 sm:items-end">
                <span
                    @class([
                        'inline-flex rounded-full px-3 py-1 text-sm font-medium',
                        'bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-200' => $ps === \App\Enums\PaymentStatus::Unpaid,
                        'bg-sky-100 text-sky-900 dark:bg-sky-950/80 dark:text-sky-200' => $ps === \App\Enums\PaymentStatus::PartiallyPaid,
                        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200' => $ps === \App\Enums\PaymentStatus::Paid,
                        'bg-orange-100 text-orange-900 dark:bg-orange-950/80 dark:text-orange-200' => $ps === \App\Enums\PaymentStatus::PartiallyRefunded,
                        'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' => $ps === \App\Enums\PaymentStatus::Voided,
                        'bg-violet-100 text-violet-900 dark:bg-violet-950/80 dark:text-violet-200' => $ps === \App\Enums\PaymentStatus::Refunded,
                    ])
                >
                    {{ $bill->payment_status->label() }}
                </span>
                @if($terminal)
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Original invoice total: :amount', ['amount' => \App\Support\Money::peso($bill->amount)]) }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        @if($ps === \App\Enums\PaymentStatus::Voided)
                            {{ __('This invoice was voided. No balance is due.') }}
                        @else
                            {{ __('This invoice was refunded. No balance is due.') }}
                        @endif
                    </div>
                    @if((float) $bill->amount_paid > 0)
                        <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                            {{ __('Payments recorded: :amount', ['amount' => \App\Support\Money::peso($bill->amount_paid)]) }}
                        </div>
                    @endif
                @else
                    <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                        {{ \App\Support\Money::peso($bill->amount_paid) }} / {{ \App\Support\Money::peso($bill->amount) }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Balance due: :amount', ['amount' => \App\Support\Money::peso($bill->balance_due)]) }}
                    </div>
                @endif
            </div>
        </div>

        <div
            class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <flux:heading size="lg" class="mb-3 text-zinc-900 dark:text-zinc-50">
                {{ __('Invoice') }}
            </flux:heading>
            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</dt>
                    <dd class="text-zinc-900 dark:text-zinc-100">
                        <time datetime="{{ $bill->created_at->toIso8601String() }}">
                            {{ $bill->created_at->format('M j, Y g:i A') }}
                        </time>
                    </dd>
                </div>
                @if($bill->order)
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Linked order') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">
                            <a
                                href="{{ route('orders.show', $bill->order) }}"
                                class="font-medium text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-800 dark:text-sky-400"
                                wire:navigate
                            >
                                {{ $bill->order->order_number }}
                            </a>
                        </dd>
                    </div>
                @endif
                @if($bill->paid_at)
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Paid at') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">
                            <time datetime="{{ $bill->paid_at->toIso8601String() }}">
                                {{ $bill->paid_at->format('M j, Y g:i A') }}
                            </time>
                        </dd>
                    </div>
                @endif
                @if(filled($bill->official_receipt_number))
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Official receipt (OR) #') }}</dt>
                        <dd class="font-mono text-zinc-900 dark:text-zinc-100">{{ $bill->official_receipt_number }}</dd>
                    </div>
                @endif
                @if($bill->payment_method)
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Payment method') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">{{ $bill->payment_method->label() }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Amount paid') }}</dt>
                    <dd class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\Money::peso($bill->amount_paid) }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Balance due') }}</dt>
                    <dd class="text-zinc-900 dark:text-zinc-100">
                        @if($terminal)
                            {{ __('—') }}
                        @else
                            {{ \App\Support\Money::peso($bill->balance_due) }}
                        @endif
                    </dd>
                </div>
            </dl>
            @if(auth()->user()?->isAdminOrStaff())
                <form
                    method="POST"
                    action="{{ route('orders.billing.official-receipt', $bill) }}"
                    class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700"
                >
                    @csrf
                    @method('PUT')
                    <label for="official_receipt_number" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Official receipt (OR) number') }}
                    </label>
                    <flux:text class="mb-2 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('BIR official receipt series/number (separate from the internal invoice above). Leave blank to clear.') }}
                    </flux:text>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <flux:input
                                id="official_receipt_number"
                                name="official_receipt_number"
                                type="text"
                                :label="false"
                                value="{{ old('official_receipt_number', $bill->official_receipt_number) }}"
                                autocomplete="off"
                                placeholder="{{ __('e.g. OR-000012345') }}"
                            />
                            @error('official_receipt_number')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <flux:button type="submit" variant="primary">
                            {{ __('Save OR number') }}
                        </flux:button>
                    </div>
                </form>
            @endif
        </div>

        @if(auth()->user()?->isAdminOrStaff() && in_array($ps, [\App\Enums\PaymentStatus::Unpaid, \App\Enums\PaymentStatus::PartiallyPaid], true))
            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-2 text-zinc-900 dark:text-zinc-50">
                    {{ __('Record payment') }}
                </flux:heading>
                <flux:text class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Record a down payment (deposit) or the balance on pickup — enter any amount up to the balance due. Full balance is filled in by default.') }}
                </flux:text>
                <flux:text class="mb-4 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('No payment gateway: choose how the customer paid (cash, e-wallet, card, or transfer).') }}
                </flux:text>
                @php
                    $recordPaymentFormId = 'record-payment-form-' . $bill->id;
                    $recordPaymentDialogId = 'record-payment-dialog-' . $bill->id;
                @endphp
                <form
                    id="{{ $recordPaymentFormId }}"
                    method="POST"
                    action="{{ route('orders.billing.pay', $bill) }}"
                    class="flex flex-col gap-4"
                    onsubmit="if (event.submitter && event.submitter.getAttribute('data-confirm-payment') === '1') { return true; } event.preventDefault(); if (this.checkValidity()) { document.getElementById({{ json_encode($recordPaymentDialogId) }}).showModal(); } else { this.reportValidity(); } return false;"
                >
                    @csrf
                    @method('PUT')
                    <div class="min-w-0 flex-1">
                        <label for="payment_amount" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Amount received') }} <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        @php
                            $bal = (float) $bill->balance_due;
                            $half = round($bal / 2, 2);
                        @endphp
                        <div class="mb-2 flex flex-wrap gap-2">
                            <flux:button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onclick="document.getElementById('payment_amount').value = {{ json_encode(number_format($bal, 2, '.', '')) }}"
                            >
                                {{ __('Pay full balance') }}
                            </flux:button>
                            @if($bal > 0)
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="document.getElementById('payment_amount').value = {{ json_encode(number_format($half, 2, '.', '')) }}"
                                >
                                    {{ __('Half (50%)') }}
                                </flux:button>
                            @endif
                        </div>
                        <flux:input
                            id="payment_amount"
                            name="payment_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            max="{{ $bill->balance_due }}"
                            :label="false"
                            value="{{ old('payment_amount', number_format((float) $bill->balance_due, 2, '.', '')) }}"
                            required
                        />
                        @error('payment_amount')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <label for="payment_method" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Payment method') }} <span class="text-red-600 dark:text-red-400">*</span>
                            </label>
                            @php
                                $selectedMethod = old('payment_method', \App\Enums\PaymentMethod::Cash->value);
                            @endphp
                            <select
                                id="payment_method"
                                name="payment_method"
                                required
                                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                            >
                                @foreach(\App\Enums\PaymentMethod::cases() as $method)
                                    <option value="{{ $method->value }}" @selected($selectedMethod === $method->value)>{{ $method->label() }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <flux:button type="submit" variant="primary">
                            {{ __('Record payment') }}
                        </flux:button>
                    </div>
                </form>
                <dialog
                    id="{{ $recordPaymentDialogId }}"
                    class="w-[calc(100%-2rem)] max-w-lg rounded-xl border border-zinc-200 bg-white p-6 text-left shadow-2xl backdrop:bg-zinc-950/60 dark:border-zinc-700 dark:bg-zinc-900"
                    onclick="if (event.target === this) this.close()"
                >
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
                            {{ __('Record this payment?') }}
                        </h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('The amount, method, and your user will be saved to the invoice and billing history.') }}
                        </p>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <form method="dialog">
                            <button
                                type="submit"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:focus:ring-offset-zinc-900"
                            >
                                {{ __('Cancel') }}
                            </button>
                        </form>
                        <button
                            type="submit"
                            form="{{ $recordPaymentFormId }}"
                            data-confirm-payment="1"
                            class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-sky-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-offset-zinc-900"
                        >
                            {{ __('Record payment') }}
                        </button>
                    </div>
                </dialog>
            </div>
        @endif

        @if(auth()->user()?->isAdmin() && $ps === \App\Enums\PaymentStatus::Unpaid)
            <div class="flex flex-wrap gap-3">
                @php
                    $voidDialogId = 'void-invoice-dialog-' . $bill->id;
                @endphp
                {{-- Native <dialog> + showModal(): reliable after wire:navigate (Flux modal registry can miss new DOM). --}}
                <div class="inline-flex">
                    <button
                        type="button"
                        class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-offset-zinc-900"
                        onclick="document.getElementById({{ json_encode($voidDialogId) }})?.showModal()"
                    >
                        {{ __('Void invoice') }}
                    </button>
                    <dialog
                        id="{{ $voidDialogId }}"
                        class="w-[calc(100%-2rem)] max-w-lg rounded-xl border border-zinc-200 bg-white p-6 text-left shadow-2xl backdrop:bg-zinc-950/60 dark:border-zinc-700 dark:bg-zinc-900"
                        onclick="if (event.target === this) this.close()"
                    >
                        <div class="space-y-2">
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
                                {{ __('Void this invoice?') }}
                            </h2>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('It will be marked voided and will no longer be collectible. This cannot be undone from the UI.') }}
                            </p>
                        </div>
                        <div class="mt-6 flex justify-end gap-2">
                            <form method="dialog">
                                <button
                                    type="submit"
                                    class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:focus:ring-offset-zinc-900"
                                >
                                    {{ __('Cancel') }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('orders.billing.void', $bill) }}" class="inline">
                                @csrf
                                @method('PUT')
                                <button
                                    type="submit"
                                    class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-offset-zinc-900"
                                >
                                    {{ __('Void invoice') }}
                                </button>
                            </form>
                        </div>
                    </dialog>
                </div>
            </div>
        @endif

        @if(auth()->user()?->isAdminOrStaff() && in_array($ps, [\App\Enums\PaymentStatus::PartiallyPaid, \App\Enums\PaymentStatus::Paid, \App\Enums\PaymentStatus::PartiallyRefunded], true))
                    <div
                        class="w-full rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                    >
                        <flux:heading size="lg" class="mb-1 text-zinc-900 dark:text-zinc-50">
                            {{ __('Record refund') }}
                        </flux:heading>
                        <flux:text class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Log the amount returned to the customer and how it was refunded. Partial refunds are supported (e.g. restocking fee). Net amount paid on the bill is reduced by the refund amount.') }}
                        </flux:text>
                            @php
                                $refundFieldClass = 'box-border block h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100';
                                $refundMethodSelected = old('refund_method', \App\Enums\PaymentMethod::Cash->value);
                                $refundFormId = 'refund-bill-form-' . $bill->id;
                                $refundDialogId = 'refund-bill-dialog-' . $bill->id;
                            @endphp
                            <form
                                id="{{ $refundFormId }}"
                                method="POST"
                                action="{{ route('orders.billing.refund', $bill) }}"
                                class="grid gap-4"
                                onsubmit="if (event.submitter && event.submitter.getAttribute('data-confirm-refund') === '1') { return true; } event.preventDefault(); if (this.checkValidity()) { document.getElementById({{ json_encode($refundDialogId) }}).showModal(); } else { this.reportValidity(); } return false;"
                            >
                            @csrf
                            @method('PUT')
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-4">
                                <div class="min-w-0 flex-1">
                                    <label for="refund_amount" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ __('Amount refunded') }} <span class="text-red-600 dark:text-red-400">*</span>
                                    </label>
                                    <input
                                        id="refund_amount"
                                        name="refund_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        max="{{ $bill->amount_paid }}"
                                        value="{{ old('refund_amount', number_format((float) $bill->amount_paid, 2, '.', '')) }}"
                                        required
                                        class="{{ $refundFieldClass }}"
                                    />
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Max: :amount (amount paid).', ['amount' => \App\Support\Money::peso($bill->amount_paid)]) }}
                                    </p>
                                    @error('refund_amount')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="min-w-0 flex-1">
                                    <label for="refund_method" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ __('Refund method') }} <span class="text-red-600 dark:text-red-400">*</span>
                                    </label>
                                    <select
                                        id="refund_method"
                                        name="refund_method"
                                        required
                                        class="{{ $refundFieldClass }}"
                                    >
                                        @foreach(\App\Enums\PaymentMethod::cases() as $method)
                                            <option value="{{ $method->value }}" @selected($refundMethodSelected === $method->value)>{{ $method->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('refund_method')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <label for="refund_note" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Note') }} <span class="text-zinc-400">({{ __('optional') }})</span>
                                </label>
                                <textarea
                                    id="refund_note"
                                    name="note"
                                    rows="2"
                                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                                    placeholder="{{ __('e.g. Returned frame, 20% restocking fee retained') }}"
                                >{{ old('note') }}</textarea>
                                @error('note')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <flux:button type="submit" variant="danger">
                                    {{ __('Submit refund') }}
                                </flux:button>
                            </div>
                            </form>
                            <dialog
                                id="{{ $refundDialogId }}"
                                class="w-[calc(100%-2rem)] max-w-lg rounded-xl border border-zinc-200 bg-white p-6 text-left shadow-2xl backdrop:bg-zinc-950/60 dark:border-zinc-700 dark:bg-zinc-900"
                                onclick="if (event.target === this) this.close()"
                            >
                                <div class="space-y-2">
                                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">
                                        {{ __('Submit this refund?') }}
                                    </h2>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ __('The refund amount, method, and optional note will be saved to the invoice and billing history.') }}
                                    </p>
                                </div>
                                <div class="mt-6 flex justify-end gap-2">
                                    <form method="dialog">
                                        <button
                                            type="submit"
                                            class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:focus:ring-offset-zinc-900"
                                        >
                                            {{ __('Cancel') }}
                                        </button>
                                    </form>
                                    <button
                                        type="submit"
                                        form="{{ $refundFormId }}"
                                        data-confirm-refund="1"
                                        class="inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-offset-zinc-900"
                                    >
                                        {{ __('Submit refund') }}
                                    </button>
                                </div>
                            </dialog>
                    </div>
        @endif

        @if(auth()->user()?->isAdminOrStaff())
            <div
                class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">
                        {{ __('Billing payment history') }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Payments, refunds (with method), voids, and updates for this invoice.') }}
                    </flux:text>
                    @if(auth()->user()->isAdmin())
                        <a
                            href="{{ route('orders.billing.payment-history.index', ['search' => $bill->invoice_number]) }}"
                            class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-800 dark:text-sky-400"
                            wire:navigate
                        >
                            {{ __('View full billing payment history') }} ->
                        </a>
                    @endif
                </div>
                @if($bill->paymentHistories->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No billing activity recorded yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                                <tr>
                                    <th class="px-4 py-3">{{ __('When') }}</th>
                                    <th class="px-4 py-3">{{ __('Actor') }}</th>
                                    <th class="px-4 py-3">{{ __('Activity') }}</th>
                                    <th class="px-4 py-3 hidden md:table-cell">{{ __('Method') }}</th>
                                    <th class="px-4 py-3 text-end hidden md:table-cell">{{ __('Amount') }}</th>
                                    <th class="px-4 py-3 hidden lg:table-cell">{{ __('From') }}</th>
                                    <th class="px-4 py-3 hidden lg:table-cell">{{ __('To') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($bill->paymentHistories as $row)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-900 dark:text-zinc-100">
                                            <time datetime="{{ $row->created_at->toIso8601String() }}">
                                                {{ $row->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                            </time>
                                        </td>
                                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                            {{ $row->actor?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                            @switch($row->action)
                                                @case(\App\Models\BillingPaymentHistory::ACTION_PAYMENT_RECORDED)
                                                    {{ __('Recorded payment') }}
                                                    @break
                                                @case(\App\Models\BillingPaymentHistory::ACTION_VOIDED)
                                                    {{ __('Voided invoice') }}
                                                    @break
                                                @case(\App\Models\BillingPaymentHistory::ACTION_REFUNDED)
                                                    {{ __('Recorded refund') }}
                                                    @break
                                                @case(\App\Models\BillingPaymentHistory::ACTION_UPDATED_FROM_ORDER_CANCELLATION)
                                                    {{ __('Updated from order cancellation') }}
                                                    @break
                                                @default
                                                    {{ $row->action }}
                                            @endswitch
                                            @if($row->note)
                                                <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">{{ $row->note }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 hidden md:table-cell">
                                            {{ $row->payment_method?->label() ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-end tabular-nums text-zinc-900 dark:text-zinc-100 hidden md:table-cell">
                                            @if($row->amount !== null)
                                                {{ \App\Support\Money::peso($row->amount) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 hidden lg:table-cell text-zinc-600 dark:text-zinc-400">
                                            {{ $row->from_payment_status?->label() ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 hidden lg:table-cell text-zinc-900 dark:text-zinc-100">
                                            {{ $row->to_payment_status->label() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if(auth()->user()?->isCustomer())
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Payments are recorded in-store. Contact staff if this invoice should be updated.') }}
            </flux:text>
        @endif
    </div>
</x-layouts::app>
