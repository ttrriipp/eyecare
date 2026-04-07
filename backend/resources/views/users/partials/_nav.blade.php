<div class="flex flex-wrap gap-2 border-b border-zinc-200 pb-4 dark:border-zinc-700">
    <flux:button
        :href="route('users.staff.index')"
        :variant="request()->routeIs('users.staff.*') ? 'primary' : 'ghost'"
        size="sm"
        wire:navigate
    >
        {{ __('Staff accounts') }}
    </flux:button>
    <flux:button
        :href="route('users.customers.index')"
        :variant="request()->routeIs('users.customers.*') ? 'primary' : 'ghost'"
        size="sm"
        wire:navigate
    >
        {{ __('Customers') }}
    </flux:button>
</div>
