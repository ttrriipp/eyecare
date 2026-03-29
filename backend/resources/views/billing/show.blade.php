@php
    $ps = $bill->payment_status;
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
                <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-50">
                    {{ $bill->invoice_number }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Orders'), 'href' => route('orders.index')],
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
                        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200' => $ps === \App\Enums\PaymentStatus::Paid,
                        'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' => $ps === \App\Enums\PaymentStatus::Voided,
                        'bg-violet-100 text-violet-900 dark:bg-violet-950/80 dark:text-violet-200' => $ps === \App\Enums\PaymentStatus::Refunded,
                    ])
                >
                    {{ $bill->payment_status->label() }}
                </span>
                <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                    {{ number_format((float) $bill->amount, 2) }} PHP
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
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
                    @if($bill->payment_method)
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Payment method') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $bill->payment_method }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if($bill->order)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <flux:heading size="lg" class="mb-3 text-zinc-900 dark:text-zinc-50">
                        {{ __('Linked order') }}
                    </flux:heading>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        <a
                            href="{{ route('orders.show', $bill->order) }}"
                            class="font-medium text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-800 dark:text-sky-400"
                            wire:navigate
                        >
                            {{ $bill->order->order_number }}
                        </a>
                    </p>
                </div>
            @endif
        </div>

        @if(auth()->user()?->isAdminOrStaff() && $ps === \App\Enums\PaymentStatus::Unpaid)
            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-2 text-zinc-900 dark:text-zinc-50">
                    {{ __('Record payment') }}
                </flux:heading>
                <flux:text class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('No payment gateway — record how the customer paid (cash, transfer, etc.).') }}
                </flux:text>
                <form method="POST" action="{{ route('orders.billing.pay', $bill) }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    @csrf
                    @method('PUT')
                    <div class="min-w-0 flex-1">
                        <label for="payment_method" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Payment method') }} <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <flux:input
                            id="payment_method"
                            name="payment_method"
                            :label="false"
                            value="{{ old('payment_method') }}"
                            placeholder="{{ __('e.g. Cash, GCash, bank transfer') }}"
                            required
                        />
                        @error('payment_method')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <flux:button type="submit" variant="primary">
                        {{ __('Mark as paid') }}
                    </flux:button>
                </form>
            </div>
        @endif

        @if(auth()->user()?->isAdmin())
            <div class="flex flex-wrap gap-3">
                @if($ps === \App\Enums\PaymentStatus::Unpaid)
                    <form method="POST" action="{{ route('orders.billing.void', $bill) }}" class="inline">
                        @csrf
                        @method('PUT')
                        <flux:button
                            type="submit"
                            variant="danger"
                            onclick="return confirm(@json(__('Void this unpaid invoice? This matches a cancelled order before payment.')))"
                        >
                            {{ __('Void invoice') }}
                        </flux:button>
                    </form>
                @endif
                @if($ps === \App\Enums\PaymentStatus::Paid)
                    <form method="POST" action="{{ route('orders.billing.refund', $bill) }}" class="inline">
                        @csrf
                        @method('PUT')
                        <flux:button
                            type="submit"
                            variant="danger"
                            onclick="return confirm(@json(__('Mark this invoice as refunded? Use when an order was cancelled after payment.')))"
                        >
                            {{ __('Record refund') }}
                        </flux:button>
                    </form>
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
