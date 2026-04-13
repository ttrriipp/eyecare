<x-layouts::auth>
    <style>
        /* Native checkbox accent matches primary actions in light and dark */
        .login-checkbox {
            accent-color: #0284c7; /* sky-600 */
        }
        .dark .login-checkbox {
            accent-color: #38bdf8; /* sky-400 */
        }
    </style>
    <div class="w-full rounded-2xl border border-transparent bg-white p-10 shadow-[0_20px_60px_rgba(0,0,0,0.12)] dark:border-zinc-700/80 dark:bg-zinc-900 dark:shadow-[0_20px_60px_rgba(0,0,0,0.45)] md:p-12">
        <div class="flex flex-col justify-center gap-5">
            <header class="space-y-2">
                <p class="text-center text-3xl font-semibold tracking-tight text-gray-800 dark:text-zinc-50">
                    {{ config('app.name', 'Laravel') }}
                </p>
            </header>

            <!-- Session Status -->
            <x-auth-session-status class="text-sm text-green-600 dark:text-green-400" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div class="space-y-4">
                    <!-- Email Address -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold tracking-wide text-gray-800 dark:text-zinc-200">
                            {{ __('Email') }}
                        </label>
                        <flux:input
                            id="email"
                            name="email"
                            :label="false"
                            :value="old('email')"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="Enter Username"
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold tracking-wide text-gray-800 dark:text-zinc-200">
                            Password
                        </label>

                        <flux:input
                            id="password"
                            name="password"
                            :label="false"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter Password"
                            viewable
                        />

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                            <label
                                for="remember"
                                class="inline-flex w-fit max-w-full cursor-pointer items-center gap-2 text-sm text-gray-700 select-none dark:text-zinc-300"
                            >
                                <input
                                    id="remember"
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    @checked(old('remember'))
                                    class="login-checkbox h-4 w-4 shrink-0 rounded border border-zinc-400 bg-white dark:border-zinc-500 dark:bg-zinc-800"
                                >
                                <span>{{ __('Remember me') }}</span>
                            </label>

                            @if (Route::has('password.request'))
                                <flux:link
                                    class="shrink-0 text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                                    :href="route('password.request')"
                                    wire:navigate
                                >
                                    {{ __('Forgot password?') }}
                                </flux:link>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <flux:button
                        variant="primary"
                        type="submit"
                        class="mt-2 inline-flex w-full cursor-pointer items-center justify-center"
                        data-test="login-button"
                    >
                        {{ __('Log in') }}
                    </flux:button>
                </div>
            </form>

            {{-- Register link intentionally hidden on login UI; route and functionality remain available elsewhere --}}
        </div>
    </div>
</x-layouts::auth>
