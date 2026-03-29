<x-layouts::app :title="__('Order :num', ['num' => $order->order_number])">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('orders.index')" wire:navigate>
                    {{ __('Back to orders') }}
                </flux:button>
                <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-50">
                    {{ $order->order_number }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Placed') }}
                    <time datetime="{{ $order->created_at->toIso8601String() }}">
                        {{ $order->created_at->format('M j, Y g:i A') }}
                    </time>
                </flux:text>
            </div>
            <div class="flex flex-col items-start gap-2 sm:items-end">
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
                    {{ __('Total: :amount PHP', ['amount' => number_format((float) $order->total_amount, 2)]) }}
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
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
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $order->user?->email ?? '—' }}</dd>
                        </div>
                        @if($order->user?->phone)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                                <dd class="tabular-nums text-zinc-900 dark:text-zinc-100">{{ $order->user->phone }}</dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </div>

            @if($order->bill)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
                >
                    <flux:heading size="lg" class="mb-3 text-zinc-900 dark:text-zinc-50">
                        {{ __('Bill') }}
                    </flux:heading>
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Invoice #') }}</dt>
                            <dd class="font-mono text-zinc-900 dark:text-zinc-100">{{ $order->bill->invoice_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</dt>
                            <dd class="tabular-nums text-zinc-900 dark:text-zinc-100">
                                {{ number_format((float) $order->bill->amount, 2) }} PHP
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Payment') }}</dt>
                            <dd>
                                <span
                                    class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200"
                                >
                                    {{ $order->bill->payment_status->label() }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>

        @if($order->notes)
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <flux:heading size="lg" class="mb-2 text-zinc-900 dark:text-zinc-50">
                    {{ __('Notes') }}
                </flux:heading>
                <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $order->notes }}</p>
            </div>
        @endif

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
                                        @if($line->product?->sku)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-500">{{ $line->product->sku }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-zinc-900 dark:text-zinc-100">
                                        {{ $line->quantity }}
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums text-zinc-600 dark:text-zinc-400 hidden sm:table-cell">
                                        {{ number_format((float) $line->unit_price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ number_format((float) $line->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
