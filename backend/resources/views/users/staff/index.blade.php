<x-layouts::app :title="__('Staff accounts')">
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

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('User management') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Staff accounts')],
                    ]"
                />
            </div>
            <flux:button variant="primary" icon="plus" :href="route('users.staff.create')" wire:navigate>
                {{ __('Add staff') }}
            </flux:button>
        </div>

        @include('users.partials._nav')

        <div
            class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <form
                method="GET"
                action="{{ route('users.staff.index') }}"
                class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end"
            >
                <div class="min-w-0 flex-1 lg:min-w-[12rem]">
                    <label for="staff-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Search') }}
                    </label>
                    <flux:input
                        id="staff-search"
                        name="search"
                        :label="false"
                        placeholder="{{ __('Name, email, phone…') }}"
                        value="{{ $filters['search'] ?? '' }}"
                    />
                </div>

                <div class="w-full min-w-0 lg:w-auto lg:min-w-[12rem]">
                    <label for="staff-status" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Status') }}
                    </label>
                    <select
                        id="staff-status"
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
                    <flux:button :href="route('users.staff.index')" variant="ghost" wire:navigate>
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @if($staffMembers->isEmpty())
                <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No staff accounts match your filters.') }}
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('Name') }}</th>
                                <th class="px-4 py-3">{{ __('Email') }}</th>
                                <th class="px-4 py-3 hidden sm:table-cell">{{ __('Phone') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($staffMembers as $member)
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $member->name }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                        {{ $member->email }}
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 hidden sm:table-cell">
                                        {{ $member->phone ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($member->trashed())
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
                                        <div class="flex flex-wrap items-center justify-center gap-2">
                                            @if(!$member->trashed())
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="pencil"
                                                    :href="route('users.staff.edit', $member)"
                                                    wire:navigate
                                                >
                                                    <span class="sr-only">{{ __('Edit') }}</span>
                                                </flux:button>
                                                @if($member->isNot(auth()->user()))
                                                    <flux:modal.trigger name="confirm-deactivate-staff-{{ $member->id }}">
                                                        <flux:button type="button" size="sm" variant="danger" icon="x-circle">
                                                            <span class="sr-only">{{ __('Deactivate') }}</span>
                                                        </flux:button>
                                                    </flux:modal.trigger>
                                                @endif
                                            @else
                                                <flux:button
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="pencil"
                                                    :href="route('users.staff.edit', $member)"
                                                    wire:navigate
                                                >
                                                    {{ __('View') }}
                                                </flux:button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($staffMembers as $member)
                    @if(!$member->trashed() && $member->isNot(auth()->user()))
                        <flux:modal name="confirm-deactivate-staff-{{ $member->id }}" focusable class="max-w-xl">
                            <div class="space-y-2">
                                <flux:heading size="lg">
                                    {{ __('Deactivate this staff account?') }}
                                </flux:heading>
                                <flux:subheading>
                                    {{ __('They will no longer be able to sign in. You can still see deactivated accounts using the status filter.') }}
                                </flux:subheading>
                            </div>
                            <div class="mt-6 flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <form method="POST" action="{{ route('users.staff.destroy', $member) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                                </form>
                            </div>
                        </flux:modal>
                    @endif
                @endforeach

                <div class="border-t border-zinc-200 px-4 py-3 text-sm dark:border-zinc-700">
                    {{ $staffMembers->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
