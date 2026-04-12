<flux:dropdown position="bottom" align="start">
    <button
        type="button"
        class="group flex w-full max-w-full items-center gap-3 rounded-lg p-2 text-start text-sm outline-none transition hover:bg-zinc-200/60 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-50 dark:hover:bg-zinc-800/80 dark:focus-visible:ring-offset-zinc-900"
        data-test="sidebar-menu-button"
    >
        <flux:avatar
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
            class="shrink-0"
        />
        <span class="min-w-0 flex-1 leading-tight">
            <span class="block truncate font-medium text-zinc-900 dark:text-zinc-100">{{ auth()->user()->name }}</span>
            <span class="block truncate text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ __(auth()->user()->role->label()) }}</span>
        </span>
        <flux:icon name="chevrons-up-down" class="size-5 shrink-0 text-zinc-400 dark:text-zinc-500" />
    </button>

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ __(auth()->user()->role->label()) }}</flux:text>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
