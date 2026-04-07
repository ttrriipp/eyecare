<x-layouts::app :title="__('Customers')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        @if($errors->has('user'))
            <div
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950/60 dark:text-red-100"
                role="alert"
            >
                {{ $errors->first('user') }}
            </div>
        @endif

        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                {{ __('User management') }}
            </flux:heading>
            <x-app-breadcrumbs
                class="mt-1.5"
                :items="[
                    ['label' => __('Home'), 'href' => route('dashboard')],
                    ['label' => __('Customers')],
                ]"
            />
        </div>

        @include('users.partials._nav')

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('users.customers.index') }}"
                class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end"
            >
                <div class="min-w-0 flex-1 lg:min-w-[12rem]">
                    <label for="cust-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Search') }}
                    </label>
                    <flux:input
                        id="cust-search"
                        name="search"
                        :label="false"
                        placeholder="{{ __('Name, email, phone…') }}"
                        value="{{ $filters['search'] ?? '' }}"
                    />
                </div>

                <div class="w-full min-w-0 lg:w-auto lg:min-w-[12rem]">
                    <label for="cust-status" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Status') }}
                    </label>
                    <select
                        id="cust-status"
                        name="status"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                        <option value="active" @selected(($filters['status'] ?? 'active') === 'active')>
                            {{ __('Active') }}
                        </option>
                        <option value="deactivated" @selected(($filters['status'] ?? '') === 'deactivated')>
                            {{ __('Deactivated') }}
                        </option>
                        <option value="all" @selected(($filters['status'] ?? '') === 'all')>
                            {{ __('All') }}
                        </option>
                    </select>
                </div>

                <div class="flex gap-2 lg:ml-auto">
                    <flux:button type="submit" variant="primary">{{ __('Apply') }}</flux:button>
                    <flux:button :href="route('users.customers.index')" variant="ghost" wire:navigate>
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @if($customers->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No customers match your filters.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Email') }}</th>
                                <th class="px-4 py-3 hidden md:table-cell">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Orders') }}</th>
                                <th class="px-4 py-3 text-center hidden sm:table-cell">{{ __('Reviews') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($customers as $c)
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                        <a
                                            href="{{ route('users.customers.show', $c) }}"
                                            class="text-sky-600 hover:underline dark:text-sky-400"
                                            wire:navigate
                                        >
                                            {{ $c->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                        {{ $c->email }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 hidden md:table-cell">
                                        {{ $c->phone ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center tabular-nums text-zinc-700 dark:text-zinc-300">
                                        {{ $c->orders_count }}
                                    </td>
                                    <td class="px-4 py-3 text-center tabular-nums text-zinc-700 dark:text-zinc-300 hidden sm:table-cell">
                                        {{ $c->feedbacks_count }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($c->trashed())
                                            <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                                                {{ __('Deactivated') }}
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200">
                                                {{ __('Active') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-center">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="eye"
                                                :href="route('users.customers.show', $c)"
                                                wire:navigate
                                            >
                                                <span class="sr-only">{{ __('View') }}</span>
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $customers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
