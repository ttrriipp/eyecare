<x-layouts::auth>
    <style>
        /* Keep login checkbox looking the same in light and dark mode */
        .login-checkbox {
            background-color: #ffffff !important;
            border-color: #9ca3af !important; /* gray-400 */
            accent-color: #0ea5e9; /* sky-500/600 style */
        }

        /* Force native form controls on login to use light theme, even when system/app is dark */
        .login-form {
            color-scheme: light;
        }
    </style>
    <div class="w-full rounded-2xl bg-white p-10 shadow-[0_20px_60px_rgba(0,0,0,0.12)] md:p-12">
        <div class="flex flex-col justify-center gap-5">
            <header class="space-y-2">
                <p class="text-3xl font-semibold tracking-tight text-center text-gray-800">
                    {{ config('app.name', 'Laravel') }}
                </p>
            </header>

            <!-- Session Status -->
            <x-auth-session-status class="text-sm text-green-600" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-6 login-form">
                @csrf

                <div class="space-y-4">
                    <!-- Email Address -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold tracking-wide text-gray-800">
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
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold tracking-wide text-gray-800">
                                Password
                            </label>

                            @if (Route::has('password.request'))
                                <flux:link
                                    class="text-xs font-medium text-zinc-500 hover:text-zinc-800"
                                    :href="route('password.request')"
                                    wire:navigate
                                >
                                    {{ __('Forgot password?') }}
                                </flux:link>
                            @endif
                        </div>

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
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            @checked(old('remember'))
                            class="login-checkbox h-4 w-4 rounded"
                        >
                        <span>{{ __('Remember me') }}</span>
                    </div>
                </div>

                <div>
                    <flux:button
                        variant="primary"
                        type="submit"
                        class="mt-2 inline-flex w-full items-center justify-center"
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
