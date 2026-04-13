<x-layouts::app :title="__('Edit staff')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Edit staff account') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Staff accounts'), 'href' => route('users.staff.index')],
                        ['label' => $staffUser->name],
                    ]"
                />
            </div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('users.staff.index')" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @include('users.partials._nav')

        @if($staffUser->trashed())
            <div
                class="max-w-xl space-y-4 rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-100"
                role="status"
            >
                <p>{{ __('This staff account is deactivated. Editing and sign-in are disabled.') }}</p>
                <dl class="grid gap-3 border-t border-amber-200/80 pt-4 dark:border-amber-800/80">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide opacity-80">{{ __('Name') }}</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $staffUser->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide opacity-80">{{ __('Email') }}</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $staffUser->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide opacity-80">{{ __('Phone') }}</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $staffUser->phone ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        @else
            <form
                method="POST"
                action="{{ route('users.staff.update', $staffUser) }}"
                class="max-w-xl space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
            >
                @csrf
                @method('PUT')

                <div class="space-y-1.5">
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Name') }}
                        <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                    </label>
                    <flux:input id="name" name="name" :label="false" value="{{ old('name', $staffUser->name) }}" required />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Email') }}
                        <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                    </label>
                    <flux:input
                        id="email"
                        name="email"
                        type="email"
                        :label="false"
                        value="{{ old('email', $staffUser->email) }}"
                        required
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Phone') }}
                    </label>
                    <flux:input id="phone" name="phone" :label="false" value="{{ old('phone', $staffUser->phone) }}" />
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('New password') }}
                    </label>
                    <flux:input
                        id="password"
                        name="password"
                        type="password"
                        :label="false"
                        placeholder="{{ __('Leave blank to keep current password') }}"
                    />
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Confirm new password') }}
                    </label>
                    <flux:input id="password_confirmation" name="password_confirmation" type="password" :label="false" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button :href="route('users.staff.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
                </div>
            </form>
        @endif

        @if(!$staffUser->trashed() && $staffUser->isNot(auth()->user()))
            <div class="max-w-xl border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:modal.trigger name="confirm-deactivate-staff-edit">
                    <flux:button type="button" variant="danger">{{ __('Deactivate account') }}</flux:button>
                </flux:modal.trigger>
                <flux:modal name="confirm-deactivate-staff-edit" focusable class="max-w-xl">
                    <div class="space-y-2">
                        <flux:heading size="lg">{{ __('Deactivate this staff account?') }}</flux:heading>
                        <flux:subheading>
                            {{ __('They will no longer be able to sign in.') }}
                        </flux:subheading>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <form method="POST" action="{{ route('users.staff.destroy', $staffUser) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" variant="danger">{{ __('Deactivate') }}</flux:button>
                        </form>
                    </div>
                </flux:modal>
            </div>
        @endif
    </div>
</x-layouts::app>
