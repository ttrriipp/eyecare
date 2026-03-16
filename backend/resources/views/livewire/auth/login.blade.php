<x-layouts::auth>
    <style>
        .eyecare-login input {
            color: #111827 !important; /* dark text */
        }

        .eyecare-login input::placeholder {
            color: #9ca3af; /* soft gray placeholder */
        }

        /* Override browser autofill so field + text stay consistent */
        .eyecare-login input:-webkit-autofill,
        .eyecare-login input:-webkit-autofill:hover,
        .eyecare-login input:-webkit-autofill:focus {
            -webkit-text-fill-color: #111827 !important;
            box-shadow: 0 0 0px 1000px #d1d5db inset;
            -webkit-box-shadow: 0 0 0px 1000px #d1d5db inset;
        }
    </style>
    <div class="eyecare-login w-full rounded-2xl bg-white p-10 shadow-[0_20px_60px_rgba(0,0,0,0.12)] md:p-12">
        <div class="flex flex-col justify-center gap-5">
            <header class="space-y-2">
                <p class="text-5xl font-bold tracking-tight text-[#1c1c1c] font-[Lexend] text-center">
                    EYECARE
                </p>
                <h1 class="text-xl font-semibold italic font-[Afacad] text-center tracking-tight text-zinc-700 white:text-zinc-50">
                    "When elegance meets convenience"
                </h1>
            </header>

            <!-- Session Status -->
            <x-auth-session-status class="text-sm text-green-600" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div class="space-y-4">
                    <!-- Email Address -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold tracking-wide text-[#1c1c1c]">
                            Username
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
                            <label for="password" class="block text-sm font-semibold tracking-wide text-[#1c1c1c] ">
                                Password
                            </label>

                            @if (Route::has('password.request'))
                                <flux:link class="text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200" :href="route('password.request')" wire:navigate>
                                    Forgot password?
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
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            @checked(old('remember'))
                            class="h-4 w-4 rounded border border-zinc-400 bg-white checked:bg-[#1c1c1c] checked:border-[#1c1c1c] focus:ring-2 focus:ring-[#1c1c1c] focus:ring-offset-1"
                        >
                        <span>Remember me</span>
                    </div>
                </div>

                <div>
                    <flux:button
                        variant="primary"
                        type="submit"
                        class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-[#1c1c1c] px-4 py-2.5 text-sm font-bold text-[#fafaff] hover:text-[#1c1c1c] transition hover:bg-gray-900 dark:bg-[#1c1c1c] dark:text-[#FAFAFF] dark:hover:bg-gray-200"
                        data-test="login-button"
                    >
                        Login
                    </flux:button>
                </div>
            </form>

            {{-- Register link intentionally hidden on login UI; route and functionality remain available elsewhere --}}
        </div>
    </div>
</x-layouts::auth>
