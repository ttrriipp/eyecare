<x-layouts::app :title="__('Products')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100"
                role="status"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Products') }}
                </flux:heading>
                <x-app-breadcrumbs
                    class="mt-1.5"
                    :items="[
                        ['label' => __('Home'), 'href' => route('dashboard')],
                        ['label' => __('Products')],
                    ]"
                />
            </div>

            @if(auth()->user()?->isAdmin())
                <div class="flex items-center gap-2">
                    <flux:button variant="ghost" icon="cog-6-tooth" :href="route('admin.settings.categories')" wire:navigate>
                        {{ __('Category settings') }}
                    </flux:button>
                    <flux:button variant="primary" icon="plus" :href="route('products.create')" wire:navigate>
                        {{ __('Add product') }}
                    </flux:button>
                </div>
            @endif
        </div>

        <livewire:products.browse />
    </div>
</x-layouts::app>
