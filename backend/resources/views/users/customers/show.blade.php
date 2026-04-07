<x-layouts::app :title="__('Customer')">
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

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ $customer->name }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Customers'), 'href' => route('users.customers.index')],
                        ['label' => $customer->name],
                    ]"
                />
            </div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('users.customers.index')" wire:navigate>
                {{ __('Back to list') }}
            </flux:button>
        </div>

        @include('users.partials._nav')

        <div class="grid gap-4 sm:grid-cols-3">
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Orders') }}
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                    {{ $customer->orders_count }}
                </div>
            </div>
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Product reviews') }}
                </div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                    {{ $customer->feedbacks_count }}
                </div>
            </div>
            <div
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    {{ __('Status') }}
                </div>
                <div class="mt-2">
                    @if($customer->trashed())
                        <span class="inline-flex rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">
                            {{ __('Deactivated') }}
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-200">
                            {{ __('Active') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div
            class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                {{ __('Contact') }}
            </h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $customer->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $customer->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Registered') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                        {{ $customer->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                    </dd>
                </div>
                @if($customer->trashed())
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Deactivated') }}</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                            {{ $customer->deleted_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="flex flex-wrap gap-3">
            @if(!$customer->trashed())
                <flux:modal.trigger name="confirm-deactivate-customer">
                    <flux:button type="button" variant="danger">{{ __('Deactivate customer') }}</flux:button>
                </flux:modal.trigger>
                <flux:modal name="confirm-deactivate-customer" focusable class="max-w-xl">
                    <div class="space-y-2">
                        <flux:heading size="lg">{{ __('Deactivate this customer account?') }}</flux:heading>
                        <flux:subheading>
                            {{ __('They will not be able to sign in. You can restore the account from the customer list when status shows deactivated or all.') }}
                        </flux:subheading>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <form method="POST" action="{{ route('users.customers.deactivate', $customer) }}" class="inline">
                            @csrf
                            <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                        </form>
                    </div>
                </flux:modal>
            @else
                <form method="POST" action="{{ route('users.customers.restore', $customer) }}" class="inline">
                    @csrf
                    <flux:button type="submit" variant="primary">{{ __('Restore account') }}</flux:button>
                </form>
            @endif
        </div>
    </div>
</x-layouts::app>
