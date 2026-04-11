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
                        'bg-sky-100 text-sky-900 dark:bg-sky-950/80 dark:text-sky-200' => $ps === \App\Enums\PaymentStatus::PartiallyPaid,
                        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200' => $ps === \App\Enums\PaymentStatus::Paid,
                        'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' => $ps === \App\Enums\PaymentStatus::Voided,
                        'bg-violet-100 text-violet-900 dark:bg-violet-950/80 dark:text-violet-200' => $ps === \App\Enums\PaymentStatus::Refunded,
                    ])
                >
                    {{ $bill->payment_status->label() }}
                </span>
                <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                    {{ \App\Support\Money::peso($bill->amount_paid) }} / {{ \App\Support\Money::peso($bill->amount) }}
                </div>
                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Balance due: :amount', ['amount' => \App\Support\Money::peso($bill->balance_due)]) }}
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
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Amount paid') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\Money::peso($bill->amount_paid) }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Balance due') }}</dt>
                        <dd class="text-zinc-900 dark:text-zinc-100">{{ \App\Support\Money::peso($bill->balance_due) }}</dd>
                    </div>
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

        @if(auth()->user()?->isAdminOrStaff() && in_array($ps, [\App\Enums\PaymentStatus::Unpaid, \App\Enums\PaymentStatus::PartiallyPaid], true))
            <div
                class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-2 text-zinc-900 dark:text-zinc-50">
                    {{ __('Record payment') }}
                </flux:heading>
                <flux:text class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('No payment gateway — record deposit or balance payment (cash, transfer, etc.).') }}
                </flux:text>
                <form method="POST" action="{{ route('orders.billing.pay', $bill) }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    @csrf
                    @method('PUT')
                    <div class="min-w-0 flex-1">
                        <label for="payment_amount" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Amount received') }} <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        <flux:input
                            id="payment_amount"
                            name="payment_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            max="{{ $bill->balance_due }}"
                            :label="false"
                            value="{{ old('payment_amount', $bill->balance_due) }}"
                            required
                        />
                        @error('payment_amount')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="min-w-0 flex-1">
                        <label for="payment_method" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Payment method') }} <span class="text-red-600 dark:text-red-400">*</span>
                        </label>
                        @php
                            $paymentMethodChoices = [
                                \App\Enums\PaymentMethod::Cash->value => __('Cash'),
                                \App\Enums\PaymentMethod::GCash->value => __('Gcash'),
                                \App\Enums\PaymentMethod::Maya->value => __('Maya'),
                                \App\Enums\PaymentMethod::BankTransfer->value => __('Bank Transfer'),
                            ];
                            $selectedMethod = old('payment_method', \App\Enums\PaymentMethod::Cash->value);
                        @endphp
                        <select
                            id="payment_method"
                            name="payment_method"
                            required
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            @foreach($paymentMethodChoices as $value => $label)
                                <option value="{{ $value }}" @selected($selectedMethod === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <flux:button type="submit" variant="primary">
                        {{ __('Record payment') }}
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
                @if(in_array($ps, [\App\Enums\PaymentStatus::PartiallyPaid, \App\Enums\PaymentStatus::Paid], true))
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

        @if(auth()->user()?->isAdmin())
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-1 text-zinc-900 dark:text-zinc-50">
                    {{ __('Billing payment history') }}
                </flux:heading>
                <flux:text class="mb-3 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('See all billing activity across the system, or filter by this invoice.') }}
                </flux:text>
                <a
                    href="{{ route('orders.billing.payment-history.index', ['search' => $bill->invoice_number]) }}"
                    class="inline-flex items-center gap-1 text-sm font-medium text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-800 dark:text-sky-400"
                    wire:navigate
                >
                    {{ __('View full billing payment history') }} ->
                </a>
            </div>
        @elseif(auth()->user()?->isStaff())
            <div
                class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">
                        {{ __('Billing payment history') }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Payments, voids, refunds, and updates for this invoice.') }}
                    </flux:text>
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
