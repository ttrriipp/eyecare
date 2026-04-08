<x-layouts::app :title="__('Supplier settings')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-100" role="status">
                {{ session('status') }}
            </div>
        @endif
        @if($errors->has('supplier'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950/60 dark:text-red-100" role="alert">
                {{ $errors->first('supplier') }}
            </div>
        @endif

        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                    {{ __('Suppliers') }}
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('Manage supplier contacts used in product setup.') }}
                </flux:text>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('products.index')" wire:navigate>
                {{ __('Back to products') }}
            </flux:button>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="xl:col-span-1">
                <form method="POST" action="{{ route('products.suppliers.store') }}" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    @csrf
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Add supplier') }}</h2>

                    <div class="space-y-1.5">
                        <label for="create_supplier_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Name') }}</label>
                        <flux:input id="create_supplier_name" name="name" :label="false" value="{{ old('name') }}" required />
                    </div>

                    <div class="space-y-1.5">
                        <label for="create_contact_person" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Contact person') }}</label>
                        <flux:input id="create_contact_person" name="contact_person" :label="false" value="{{ old('contact_person') }}" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="create_phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Phone') }}</label>
                        <flux:input id="create_phone" name="phone" :label="false" value="{{ old('phone') }}" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="create_email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Email') }}</label>
                        <flux:input id="create_email" name="email" type="email" :label="false" value="{{ old('email') }}" />
                    </div>

                    <label class="flex items-start gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="mt-0.5 size-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-900">
                        <span>{{ __('Active') }}</span>
                    </label>

                    <flux:button type="submit" variant="primary" icon="plus">{{ __('Create supplier') }}</flux:button>
                </form>
            </div>

            <div class="xl:col-span-2">
                <form method="GET" action="{{ route('products.suppliers.index') }}" class="mb-3 flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:flex-row sm:items-center">
                    <div class="sm:flex-1">
                        <flux:input
                            name="q"
                            :label="false"
                            value="{{ $filters['q'] ?? '' }}"
                            placeholder="{{ __('Search name, contact, phone, or email') }}"
                        />
                    </div>
                    <div class="sm:w-44">
                        <flux:select name="status" :label="false">
                            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>{{ __('All statuses') }}</option>
                            <option value="active" @selected(($filters['status'] ?? 'all') === 'active')>{{ __('Active only') }}</option>
                            <option value="inactive" @selected(($filters['status'] ?? 'all') === 'inactive')>{{ __('Inactive only') }}</option>
                        </flux:select>
                    </div>
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary" size="sm">{{ __('Filter') }}</flux:button>
                        <flux:button type="button" variant="ghost" size="sm" :href="route('products.suppliers.index')" wire:navigate>{{ __('Reset') }}</flux:button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Contact') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Active') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($suppliers as $supplier)
                                <tr>
                                    <td class="px-4 py-3"><flux:input form="update-supplier-{{ $supplier->id }}" name="name" :label="false" value="{{ $supplier->name }}" required /></td>
                                    <td class="px-4 py-3"><flux:input form="update-supplier-{{ $supplier->id }}" name="contact_person" :label="false" value="{{ $supplier->contact_person }}" /></td>
                                    <td class="px-4 py-3"><flux:input form="update-supplier-{{ $supplier->id }}" name="phone" :label="false" value="{{ $supplier->phone }}" /></td>
                                    <td class="px-4 py-3"><flux:input form="update-supplier-{{ $supplier->id }}" name="email" type="email" :label="false" value="{{ $supplier->email }}" /></td>
                                    <td class="px-4 py-3 text-center">
                                        <input form="update-supplier-{{ $supplier->id }}" type="hidden" name="is_active" value="0">
                                        <input form="update-supplier-{{ $supplier->id }}" type="checkbox" name="is_active" value="1" @checked((bool) $supplier->is_active) class="size-4 rounded border-zinc-400 text-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-900">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button type="submit" form="update-supplier-{{ $supplier->id }}" class="inline-flex h-8 items-center rounded-md bg-sky-600 px-3 text-xs font-medium text-white shadow-sm hover:bg-sky-700">{{ __('Save') }}</button>
                                            <form method="POST" action="{{ route('products.suppliers.destroy', $supplier) }}" data-confirm-message="{{ __('Delete this supplier? This fails if products still use it.') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 items-center rounded-md bg-red-600 px-3 text-xs font-medium text-white shadow-sm hover:bg-red-700">{{ __('Delete') }}</button>
                                            </form>
                                            <form id="update-supplier-{{ $supplier->id }}" method="POST" action="{{ route('products.suppliers.update', $supplier) }}" class="hidden">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                        </div>
                                        <p class="mt-1 text-right text-xs text-zinc-500 dark:text-zinc-500">
                                            {{ __('Used by :count product(s)', ['count' => $supplier->products_count]) }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                            @if($suppliers->isEmpty())
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ __('No suppliers match the current filter.') }}
                                    </td>
                                </tr>
                            @endif
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
