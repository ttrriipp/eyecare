<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
            {{ __('Dashboard') }}
        </flux:heading>
        <flux:text class="text-zinc-600 dark:text-zinc-400">
            {{ __('Welcome back. Use the sidebar to manage products and inventory.') }}
        </flux:text>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
            >
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-zinc-100/20" />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
            >
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-zinc-100/20" />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
            >
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-zinc-100/20" />
            </div>
        </div>
        <div
            class="relative h-full min-h-[12rem] flex-1 overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
        >
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-zinc-100/20" />
        </div>
    </div>
</x-layouts::app>
