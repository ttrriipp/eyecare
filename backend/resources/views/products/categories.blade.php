<x-layouts::app :title="__('Category settings')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100" role="status">
                {{ session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950/60 dark:text-red-100" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Product categories') }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Manage AR support and expiry tracking rules per category.') }}
                </flux:text>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('products.index')" wire:navigate>
                {{ __('Back to products') }}
            </flux:button>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="xl:col-span-1">
                <form method="POST" action="{{ route('products.categories.store') }}" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    @csrf
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Add category') }}</h2>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300" for="create_name">{{ __('Name') }}</label>
                        <flux:input id="create_name" name="name" :label="false" value="{{ old('name') }}" required />
                        @error('name')<p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>


                    <label class="flex items-start gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                        <input type="checkbox" name="has_ar_support" value="1" @checked(old('has_ar_support')) class="mt-0.5 size-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-900">
                        <span>{{ __('Supports AR preview') }}</span>
                    </label>

                    <label class="flex items-start gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                        <input type="checkbox" name="requires_expiry_tracking" value="1" @checked(old('requires_expiry_tracking')) class="mt-0.5 size-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-900">
                        <span>{{ __('Requires expiry tracking') }}</span>
                    </label>

                    <flux:button type="submit" variant="primary" icon="plus">{{ __('Create category') }}</flux:button>
                </form>
            </div>

            <div class="xl:col-span-2">
                <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Slug') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('AR') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Expiry') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($categories as $category)
                                <tr>
                                    <td class="px-4 py-3">
                                        <flux:input form="update-category-{{ $category->id }}" name="name" :label="false" value="{{ $category->name }}" required />
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $category->slug }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="inline-flex items-center gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                                            <input form="update-category-{{ $category->id }}" type="hidden" name="has_ar_support" value="0">
                                            <input form="update-category-{{ $category->id }}" type="checkbox" name="has_ar_support" value="1" @checked((bool) $category->has_ar_support) class="size-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-900">
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="inline-flex items-center gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                                            <input form="update-category-{{ $category->id }}" type="hidden" name="requires_expiry_tracking" value="0">
                                            <input form="update-category-{{ $category->id }}" type="checkbox" name="requires_expiry_tracking" value="1" @checked((bool) $category->requires_expiry_tracking) class="size-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-900">
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="submit" form="update-category-{{ $category->id }}" class="inline-flex h-8 items-center rounded-md bg-sky-600 px-3 text-xs font-medium text-white shadow-sm hover:bg-sky-700">
                                                {{ __('Save') }}
                                            </button>
                                            <form
                                                id="delete-category-{{ $category->id }}"
                                                method="POST"
                                                action="{{ route('products.categories.destroy', $category) }}"
                                                data-confirm-message="{{ __('Delete this category? This fails if products still use it.') }}"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 items-center rounded-md bg-red-600 px-3 text-xs font-medium text-white shadow-sm hover:bg-red-700">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                            <form id="update-category-{{ $category->id }}" method="POST" action="{{ route('products.categories.update', $category) }}" class="hidden">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form[data-confirm-message]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
                    if (!window.confirm(message)) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
</x-layouts::app>
