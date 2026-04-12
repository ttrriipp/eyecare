<x-layouts::app :title="__('Add staff')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
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
            class="max-w-2xl space-y-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none"
        >
            @csrf

            <div>
                <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Profile & contact') }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('They sign in with the email and password you set below. Phone is optional.') }}
                </flux:text>

                <div class="mt-5 grid gap-5 sm:grid-cols-2 sm:items-start">
                    <div class="space-y-1.5">
                        <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Name') }}
                            <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                        </label>
                        <flux:input
                            id="name"
                            name="name"
                            :label="false"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                            autofocus
                        />
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
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Phone') }}
                        </label>
                        <flux:input
                            id="phone"
                            name="phone"
                            type="tel"
                            :label="false"
                            value="{{ old('phone') }}"
                            autocomplete="tel"
                        />
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-zinc-200 pt-8 dark:border-zinc-700">
                <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Password') }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Minimum 8 characters. Stricter rules apply in production.') }}
                </flux:text>

                <div class="mt-5 grid gap-5 sm:grid-cols-2 sm:items-start">
                    <div class="space-y-1.5">
                        <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Password') }}
                            <span class="ml-0.5 text-red-500" aria-hidden="true">*</span>
                        </label>
                        <flux:input
                            id="password"
                            name="password"
                            type="password"
                            :label="false"
                            autocomplete="new-password"
                            required
                        />
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
                            autocomplete="new-password"
                            required
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-6 sm:flex-row sm:justify-end dark:border-zinc-700">
                <flux:button :href="route('users.staff.index')" variant="ghost" wire:navigate class="w-full sm:w-auto">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                    {{ __('Create staff account') }}
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
