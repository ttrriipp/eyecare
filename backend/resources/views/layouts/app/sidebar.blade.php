<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen">
        <flux:sidebar
            sticky
            collapsible="mobile"
            class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900"
        >
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item
                        icon="home"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                    >
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>


                    @if(auth()->user()?->isAdminOrStaff())
                        <flux:sidebar.item
                            icon="layout-grid"
                            :href="route('products.index')"
                            :current="request()->routeIs('products.*', 'admin.inventory.*')"
                            wire:navigate
                        >
                            {{ __('Products') }}
                        </flux:sidebar.item>
                    @else
                        <flux:sidebar.item
                            icon="layout-grid"
                            :href="route('products.index')"
                            :current="request()->routeIs('products.*')"
                            wire:navigate
                        >
                            {{ __('Products') }}
                        </flux:sidebar.item>
                    @endif

                    <flux:sidebar.item
                        icon="clipboard-document-list"
                        :href="route('orders.index')"
                        :current="request()->routeIs('orders.index', 'orders.create', 'orders.show', 'orders.status-history.index')"
                        wire:navigate
                    >
                        {{ __('Orders') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="banknotes"
                        :href="route('orders.billing.index')"
                        :current="request()->routeIs('orders.billing.index', 'orders.billing.show', 'orders.billing.payment-history.index')"
                        wire:navigate
                    >
                        {{ __('Billing') }}
                    </flux:sidebar.item>

                    @if(auth()->user()?->isAdminOrStaff())
                        <flux:sidebar.item
                            icon="star"
                            :href="route('feedbacks.index')"
                            :current="request()->routeIs('feedbacks.*')"
                            wire:navigate
                        >
                            {{ __('Feedback') }}
                        </flux:sidebar.item>
                    @endif

                    @if(auth()->user()?->isAdmin())
                        <flux:sidebar.item
                            icon="users"
                            :href="route('users.staff.index')"
                            :current="request()->routeIs('users.*')"
                            wire:navigate
                        >
                            {{ __('Users') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>

                @if(auth()->user()?->isAdmin())
                    <flux:sidebar.group :heading="__('Settings')">
                        <flux:sidebar.item
                            icon="tag"
                            :href="route('admin.settings.categories')"
                            :current="request()->routeIs('admin.settings.categories')"
                            wire:navigate
                        >
                            {{ __('Categories') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:modal.trigger name="confirm-logout">
                        <flux:menu.item
                            as="button"
                            type="button"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </flux:modal.trigger>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <flux:modal name="confirm-logout" focusable class="max-w-lg">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Log out') }}</flux:heading>
                <flux:subheading>
                    {{ __('Do you want to log out?') }}
                </flux:subheading>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <flux:button type="submit" variant="primary">{{ __('Log Out') }}</flux:button>
                </form>
            </div>
        </flux:modal>

        @fluxScripts
    </body>
</html>
