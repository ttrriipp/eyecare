<x-layouts::app :title="__('Add staff')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Add staff account') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Staff accounts'), 'href' => route('users.staff.index')],
                        ['label' => __('Add staff')],
                    ]"
                />
            </div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('users.staff.index')" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>

        @include('users.partials._nav')

        <form
            method="POST"
            action="{{ route('users.staff.store') }}"
            class="max-w-xl space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @csrf

            <div class="space-y-1.5">
                <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Name') }}
                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                </label>
                <flux:input id="name" name="name" :label="false" value="{{ old('name') }}" required autofocus />
                @error('name')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Email') }}
                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                </label>
                <flux:input id="email" name="email" type="email" :label="false" value="{{ old('email') }}" required />
                @error('email')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Phone') }}
                </label>
                <flux:input id="phone" name="phone" :label="false" value="{{ old('phone') }}" />
                @error('phone')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Password') }}
                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                </label>
                <flux:input id="password" name="password" type="password" :label="false" required />
                @error('password')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Confirm password') }}
                    <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                </label>
                <flux:input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    :label="false"
                    required
                />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button :href="route('users.staff.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create staff account') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
