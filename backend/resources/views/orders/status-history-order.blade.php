<x-layouts::app :title="__('Order :num — status history', ['num' => $order->order_number])">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('orders.show', $order)" wire:navigate>
                    {{ __('Back to order') }}
                </flux:button>
                <flux:heading size="xl" class="mt-2 text-zinc-900 dark:text-zinc-50">
                    {{ __('Status history') }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Order :number', ['number' => $order->order_number]) }}
                </flux:text>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Orders'), 'href' => route('orders.index')],
                        ['label' => $order->order_number, 'href' => route('orders.show', $order)],
                        ['label' => __('Status history')],
                    ]"
                />
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @if($entries->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
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
                            @foreach($entries as $row)
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
                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $entries->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
