@php
    $nextStatuses = collect(\App\Enums\OrderStatus::cases())->filter(
        fn (\App\Enums\OrderStatus $st) => $order->status->canTransitionTo($st),
    );
    $canCustomerCancel = auth()->user()?->isCustomer()
        && $order->canBeCancelledBy(auth()->user());
@endphp

<x-layouts::app :title="__('Order :num', ['num' => $order->order_number])">
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
            <div class="min-w-0 flex-1">
                <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('orders.index')" wire:navigate>
                    {{ __('Back to orders') }}
                </flux:button>
                <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-50">
                    {{ $order->order_number }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Placed') }}
                    <time datetime="{{ $order->created_at->toIso8601String() }}">
                        {{ $order->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                    </time>
                </flux:text>
            </div>
            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                @php
                    $s = $order->status;
                @endphp
                <span
                    @class([
                        'inline-flex rounded-full px-3 py-1 text-sm font-medium',
                        'bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-200' => $s === \App\Enums\OrderStatus::Pending,
                        'bg-sky-100 text-sky-900 dark:bg-sky-950/80 dark:text-sky-200' => $s === \App\Enums\OrderStatus::Confirmed,
                        'bg-violet-100 text-violet-900 dark:bg-violet-950/80 dark:text-violet-200' => $s === \App\Enums\OrderStatus::ReadyForPickup,
                        'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200' => $s === \App\Enums\OrderStatus::Completed,
                        'bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' => $s === \App\Enums\OrderStatus::Cancelled,
                    ])
                >
                    {{ $order->status->label() }}
                </span>
                <div class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                    {{ \App\Support\Money::peso($order->total_amount) }}
                </div>
                @if((float) $order->discount_amount > 0)
                    <div class="text-sm tabular-nums text-zinc-600 dark:text-zinc-400">
                        {{ __('Includes :amount discount', ['amount' => \App\Support\Money::peso($order->discount_amount)]) }}
                    </div>
                @endif
            </div>
        </div>

        @if(auth()->user()?->isAdminOrStaff() && $nextStatuses->isNotEmpty())
            <div
                x-data="{
                    selectedStatus: '',
                    syncSelectedStatus() {
                        const select = this.$refs.orderStatus;
                        this.selectedStatus = select?.options?.[select.selectedIndex]?.text ?? '';
                    },
                }"
                x-init="syncSelectedStatus()"
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-2 text-zinc-900 dark:text-zinc-50">
                    {{ __('Update status') }}
                </flux:heading>
                <flux:text class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Move this order through pickup: Pending → Confirmed → Ready for Pickup → Completed. Cancel voids or refunds the bill when applicable.') }}
                </flux:text>
                <form
                    id="order-status-form"
                    method="POST"
                    action="{{ route('orders.status.update', $order) }}"
                    class="flex flex-col gap-4 sm:flex-row sm:items-end"
                >
                    @csrf
                    @method('PUT')
                    <div class="min-w-0 flex-1 sm:max-w-xs">
                        <label for="order-status" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('New status') }}
                        </label>
                        <select
                            id="order-status"
                            name="status"
                            x-ref="orderStatus"
                            x-on:change="syncSelectedStatus()"
                            required
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                        >
                            @foreach($nextStatuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <flux:modal.trigger name="confirm-order-status-update">
                        <flux:button type="button" variant="primary" x-on:click="syncSelectedStatus()">
                            {{ __('Apply') }}
                        </flux:button>
                    </flux:modal.trigger>
                </form>

                <flux:modal name="confirm-order-status-update" focusable class="max-w-xl">
                    <div class="space-y-2 pr-8">
                        <flux:heading size="lg">
                            <span
                                x-text="`Do you want to update the current status of {{ $order->order_number }} to ${selectedStatus}?`"
                            ></span>
                        </flux:heading>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary" form="order-status-form">
                            {{ __('Apply') }}
                        </flux:button>
                    </div>
                </flux:modal>
            </div>
        @elseif($canCustomerCancel)
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-2 text-zinc-900 dark:text-zinc-50">
                    {{ __('Cancel order') }}
                </flux:heading>
                <flux:text class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('You can cancel before the order is ready for pickup.') }}
                </flux:text>
                <form method="POST" action="{{ route('orders.status.update', $order) }}" class="inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ \App\Enums\OrderStatus::Cancelled->value }}">
                    <flux:button
                        type="submit"
                        variant="danger"
                        onclick="return confirm(@json(__('Cancel this order? Your bill will be voided if unpaid or refunded if already paid.')))"
                    >
                        {{ __('Cancel my order') }}
                    </flux:button>
                </form>
                @error('status')
                    <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @php
            $linkedAppointment = ($order->appointment_id && $order->appointment) ? $order->appointment : null;
            $detailGridClass = $linkedAppointment ? 'lg:grid-cols-3' : 'lg:grid-cols-2';
        @endphp

        <div class="grid gap-6 {{ $detailGridClass }}">
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-3 text-zinc-900 dark:text-zinc-50">
                    {{ __('Customer') }}
                </flux:heading>
                @if($order->isWalkIn())
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $order->walk_in_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">—</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                            <dd class="tabular-nums text-zinc-900 dark:text-zinc-100">{{ $order->walk_in_phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <span
                                class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200"
                            >
                                {{ __('Walk-in') }}
                            </span>
                        </div>
                    </dl>
                @else
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $order->user?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                            <dd class="break-all text-zinc-900 dark:text-zinc-100">{{ $order->user?->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                            <dd class="tabular-nums text-zinc-900 dark:text-zinc-100">{{ $order->user?->phone ?? '—' }}</dd>
                        </div>
                    </dl>
                @endif
            </div>

            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-3 text-zinc-900 dark:text-zinc-50">
                    {{ __('Bill') }}
                </flux:heading>
                @if($order->bill)
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Invoice number') }}</dt>
                            <dd class="font-mono text-zinc-900 dark:text-zinc-100">{{ $order->bill->invoice_number }}</dd>
                        </div>
                        @if(filled($order->bill->official_receipt_number))
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Official receipt (OR) #') }}</dt>
                                <dd class="font-mono text-zinc-900 dark:text-zinc-100">{{ $order->bill->official_receipt_number }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</dt>
                            <dd class="tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ \App\Support\Money::peso($order->bill->amount) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Payment status') }}</dt>
                            <dd>
                                <span
                                    class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                                >
                                    {{ $order->bill->payment_status->label() }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('No bill has been created for this order yet. A bill is usually added when payment is recorded.') }}
                    </p>
                @endif
            </div>

            @if($linkedAppointment)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <flux:heading size="lg" class="mb-3 text-zinc-900 dark:text-zinc-50">
                        {{ __('Appointment') }}
                    </flux:heading>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">
                                {{ $linkedAppointment->scheduled_at?->timezone(config('app.timezone'))->format('M j, Y') ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Time') }}</dt>
                            <dd class="tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ $linkedAppointment->scheduled_at?->timezone(config('app.timezone'))->format('g:i A') ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Service type') }}</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">
                                {{ $linkedAppointment->appointment_type ?? __('Appointment') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>

        <div
            class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <flux:heading size="lg" class="mb-2 text-zinc-900 dark:text-zinc-50">
                {{ __('Notes') }}
            </flux:heading>
            @if(filled($order->notes))
                <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $order->notes }}</p>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No notes on this order.') }}</p>
            @endif
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Line items') }}
                </flux:heading>
            </div>
            @if($order->items->isEmpty())
                <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No line items.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Product') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Qty') }}</th>
                                <th class="px-4 py-3 text-end hidden sm:table-cell">{{ __('Unit price') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($order->items as $line)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $line->product?->name ?? __('Unknown product') }}
                                        </div>
                                        @if($line->productVariant?->sku)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-500">{{ $line->productVariant->sku }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                        {{ $line->quantity }}
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-zinc-600 dark:text-zinc-400 hidden sm:table-cell">
                                        {{ \App\Support\Money::peso($line->unit_price) }}
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ \App\Support\Money::peso($line->subtotal) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if(auth()->user()?->isAdminOrStaff())
            <div
                class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">
                        {{ __('Order status history') }}
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Status changes and who recorded them for this order.') }}
                    </flux:text>
                    @if(auth()->user()->isAdmin())
                        <a
                            href="{{ route('orders.status-history.index', ['search' => $order->order_number]) }}"
                            class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-sky-600 underline decoration-sky-300 underline-offset-2 hover:text-sky-800 dark:text-sky-400"
                            wire:navigate
                        >
                            {{ __('View full order status history') }} ->
                        </a>
                    @endif
                </div>
                @if($order->statusHistories->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No status activity recorded for this order yet.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                                <tr>
                                    <th class="px-4 py-3">{{ __('When') }}</th>
                                    <th class="px-4 py-3">{{ __('Staff') }}</th>
                                    <th class="px-4 py-3">{{ __('Activity') }}</th>
                                    <th class="px-4 py-3 hidden sm:table-cell">{{ __('From') }}</th>
                                    <th class="px-4 py-3 hidden sm:table-cell">{{ __('To') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($order->statusHistories as $row)
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
                                            @if($row->action === \App\Models\OrderStatusHistory::ACTION_CREATED)
                                                {{ __('Created order') }}
                                            @else
                                                {{ __('Updated status') }}
                                            @endif
                                        </td>
                                        <td class="hidden px-4 py-3 text-zinc-600 dark:text-zinc-400 sm:table-cell">
                                            {{ $row->from_status?->label() ?? '—' }}
                                        </td>
                                        <td class="hidden px-4 py-3 text-zinc-900 dark:text-zinc-100 sm:table-cell">
                                            {{ $row->to_status->label() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts::app>
