@php
    $stats = $this->categoryStats();
    $systemCats = $this->categories->where('is_system', true)->values();
    $customCats  = $this->categories->where('is_system', false)->values();

    $variantFlagLabels = [
        'has_color'       => 'Color',
        'has_frame_size'  => 'Frame size',
        'has_material'    => 'Material',
        'has_lens_type'   => 'Lens type',
        'has_power_field' => 'Power',
        'has_duration'    => 'Duration',
    ];
@endphp

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

    {{-- ── Page header ───────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-zinc-900 dark:text-zinc-50">
                {{ __('Product categories') }}
            </flux:heading>
            <flux:text class="mt-0.5 text-zinc-600 dark:text-zinc-400">
                {{ __('Manage categories and their behavior flags for product variants.') }}
            </flux:text>
        </div>

        @if(auth()->user()?->isAdmin())
            <flux:button
                wire:click="openAdd"
                variant="primary"
                icon="plus"
                class="shrink-0"
            >
                {{ __('New category') }}
            </flux:button>
        @endif
    </div>

    {{-- ── Stat chips ─────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {{-- Total --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-950/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $stats['total'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</p>
            </div>
        </div>

        {{-- System --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $stats['system'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('System') }}</p>
            </div>
        </div>

        {{-- Custom --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $stats['custom'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Custom') }}</p>
            </div>
        </div>

        {{-- AR --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-950/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                </svg>
            </div>
            <div>
                <p class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $stats['ar'] }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('With AR') }}</p>
            </div>
        </div>
    </div>

    {{-- ── Category table ──────────────────────────────────────────────────── --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:shadow-none">
        <table class="w-full min-w-[70rem] text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-400">
                    <th class="px-4 py-3.5">{{ __('Name / Description') }}</th>
                    <th class="px-4 py-3.5 text-center">{{ __('Products') }}</th>
                    <th class="px-4 py-3.5">{{ __('AR try-on') }}</th>
                    <th class="px-4 py-3.5">{{ __('Requires Rx') }}</th>
                    <th class="px-4 py-3.5">{{ __('Stock unit') }}</th>
                    <th class="px-4 py-3.5">{{ __('Variant fields') }}</th>
                    <th class="px-4 py-3.5">{{ __('Type') }}</th>
                    <th class="px-4 py-3.5 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">

                {{-- System categories --}}
                @foreach($systemCats as $cat)
                    @php
                        $flags = collect($variantFlagLabels)
                            ->filter(fn($label, $key) => $cat->{$key})
                            ->values();
                    @endphp
                    <tr wire:key="cat-{{ $cat->id }}" class="bg-white hover:bg-zinc-50/70 dark:bg-zinc-900 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3.5 align-middle">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $cat->name }}</div>
                            @if($cat->description)
                                <div class="mt-0.5 max-w-[260px] truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $cat->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center align-middle tabular-nums text-zinc-700 dark:text-zinc-300">
                            {{ $cat->products_count }}
                        </td>
                        <td class="px-4 py-3.5 align-middle">
                            @if($cat->has_ar_support)
                                <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-medium text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">{{ __('AR enabled') }}</span>
                            @else
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('No') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-middle">
                            @if($cat->requires_prescription)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Required') }}</span>
                            @else
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('No') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-middle text-sm text-zinc-700 capitalize dark:text-zinc-300">
                            {{ $cat->stock_unit ?? 'units' }}
                        </td>
                        <td class="px-4 py-3.5 align-middle">
                            @if($flags->isNotEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @foreach($flags as $label)
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $label }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __('None') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-middle">
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" /></svg>
                                {{ __('System') }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-right align-middle">
                            @if(auth()->user()?->isAdmin())
                                <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $cat->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @endforeach

                {{-- Custom categories --}}
                @if($customCats->isNotEmpty())
                    @foreach($customCats as $cat)
                        @php
                            $flags = collect($variantFlagLabels)
                                ->filter(fn($label, $key) => $cat->{$key})
                                ->values();
                        @endphp
                        <tr wire:key="cat-{{ $cat->id }}" class="bg-white hover:bg-zinc-50/70 dark:bg-zinc-900 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3.5 align-middle">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $cat->name }}</div>
                                @if($cat->description)
                                    <div class="mt-0.5 max-w-[260px] truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $cat->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center align-middle tabular-nums text-zinc-700 dark:text-zinc-300">
                                {{ $cat->products_count }}
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                @if($cat->has_ar_support)
                                    <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-[11px] font-medium text-purple-800 dark:bg-purple-950/60 dark:text-purple-300">{{ __('AR enabled') }}</span>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                @if($cat->requires_prescription)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">{{ __('Required') }}</span>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle text-sm text-zinc-700 capitalize dark:text-zinc-300">
                                {{ $cat->stock_unit ?? 'units' }}
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                @if($flags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($flags as $label)
                                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ $label }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs italic text-zinc-400 dark:text-zinc-500">{{ __('None') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-middle">
                                <span class="inline-flex items-center rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ __('Custom') }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right align-middle">
                                @if(auth()->user()?->isAdmin())
                                    <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $cat->id }})">
                                        {{ __('Edit') }}
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    {{-- Empty state: no custom categories --}}
                    <tr>
                        <td colspan="8" class="px-4 py-14 text-center">
                            <div class="mx-auto flex max-w-xs flex-col items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('No custom categories yet.') }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Add one to get started.') }}</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         Slide-over overlay & panel
         Always rendered — classes toggled by $showPanel for smooth CSS transition.
         ═══════════════════════════════════════════════════════════════════════ --}}

    {{-- Backdrop --}}
    <div
        wire:click="closePanel"
        aria-hidden="true"
        class="fixed inset-0 z-40 bg-black/40 transition-opacity duration-300 ease-in-out
               {{ $showPanel ? 'opacity-100' : 'pointer-events-none opacity-0' }}"
    ></div>

    {{-- Slide-over panel --}}
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="panel-title"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[480px] flex-col bg-white shadow-2xl transition-transform duration-300 ease-in-out
               dark:bg-zinc-900 sm:border-l sm:border-zinc-200 dark:sm:border-zinc-700
               {{ $showPanel ? 'translate-x-0' : 'translate-x-full' }}"
    >
        {{-- Panel header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 id="panel-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-50">
                @if($mode === 'add')
                    {{ __('New category') }}
                @else
                    {{ __('Edit') }} — <span class="font-normal text-zinc-600 dark:text-zinc-300">{{ $editingName }}</span>
                @endif
            </h2>
            <button
                type="button"
                wire:click="closePanel"
                class="flex h-8 w-8 items-center justify-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                aria-label="{{ __('Close panel') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        </div>

        {{-- ── Panel body (scrollable) ─────────────────────────────────────── --}}
        <div class="flex-1 overflow-y-auto">

            @if($showDeleteConfirm)
                {{-- ─── Delete confirmation state ─────────────────────────── --}}
                <div class="flex flex-col items-start gap-4 p-5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-950/60">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">
                            {{ __('Delete this category?') }}
                        </h3>

                        @if($editingProductsCount === 0)
                            <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('This will permanently delete') }}
                                <strong class="font-medium text-zinc-800 dark:text-zinc-200">'{{ $editingName }}'</strong>.
                                {{ __('This cannot be undone.') }}
                            </p>
                        @else
                            <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                                <strong class="font-medium text-zinc-800 dark:text-zinc-200">'{{ $editingName }}'</strong>
                                {{ __('has') }}
                                <strong class="font-medium text-zinc-800 dark:text-zinc-200">{{ $editingProductsCount }}</strong>
                                {{ \Illuminate\Support\Str::plural('product', $editingProductsCount) }}
                                {{ __('linked to it. You must reassign or delete those products before this category can be removed.') }}
                            </p>
                        @endif

                        @if($deleteError)
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $deleteError }}</p>
                        @endif
                    </div>
                </div>

            @else
                {{-- ─── Form state ─────────────────────────────────────────── --}}
                <div class="space-y-6 p-5">

                    {{-- Section 1: Basic info --}}
                    <div class="space-y-4">

                        {{-- Name --}}
                        <div>
                            <label for="cat-name" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Name') }}
                                <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input
                                wire:model.live="name"
                                id="cat-name"
                                type="text"
                                maxlength="80"
                                placeholder="{{ __('e.g. Sports eyewear') }}"
                                class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition
                                       focus:outline-none focus:ring-1
                                       {{ $errors->has('name')
                                           ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                           : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                                       bg-white text-zinc-900 placeholder:text-zinc-400
                                       dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                                autocomplete="off"
                            >
                            <div class="mt-1 flex items-start justify-between gap-2">
                                @error('name')
                                    <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                @else
                                    <span></span>
                                @enderror
                                @if(strlen($name) >= 60)
                                    <p class="shrink-0 text-right text-xs {{ strlen($name) >= 75 ? 'text-red-500' : 'text-amber-500' }}">
                                        {{ strlen($name) }}/80
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label for="cat-desc" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Description') }}
                                <span class="text-zinc-400 dark:text-zinc-500">({{ __('optional') }})</span>
                            </label>
                            <textarea
                                wire:model.live="description"
                                id="cat-desc"
                                rows="2"
                                maxlength="300"
                                placeholder="{{ __('Short description of what belongs in this category.') }}"
                                class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition
                                       focus:outline-none focus:ring-1
                                       {{ $errors->has('description')
                                           ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                           : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                                       bg-white text-zinc-900 placeholder:text-zinc-400
                                       dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500"
                            ></textarea>
                            <div class="mt-1 flex items-start justify-between gap-2">
                                @error('description')
                                    <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                @else
                                    <span></span>
                                @enderror
                                @if(strlen($description) >= 250)
                                    <p class="shrink-0 text-right text-xs {{ strlen($description) >= 285 ? 'text-red-500' : 'text-amber-500' }}">
                                        {{ strlen($description) }}/300
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Stock unit --}}
                        <div>
                            <label for="cat-stock-unit" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Stock unit') }}
                                <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <select
                                wire:model.live="stock_unit"
                                id="cat-stock-unit"
                                class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition
                                       focus:outline-none focus:ring-1
                                       {{ $errors->has('stock_unit')
                                           ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                                           : 'border-zinc-300 focus:border-sky-500 focus:ring-sky-500 dark:border-zinc-600' }}
                                       bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                <option value="units">{{ __('Units') }}</option>
                                <option value="pairs">{{ __('Pairs') }}</option>
                                <option value="boxes">{{ __('Boxes') }}</option>
                            </select>
                            @error('stock_unit')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __("Controls how stock quantity is labeled in inventory. Use 'Pairs' for prescription lenses, 'Boxes' for contact lenses, 'Units' for everything else.") }}
                            </p>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-zinc-200 dark:border-zinc-700"></div>

                    {{-- Section 2: Behavior flags --}}
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Behavior flags') }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('These control system behavior and which variant fields appear when adding products under this category.') }}
                        </p>
                    </div>

                    {{-- System category lock notice --}}
                    @if($is_system)
                        <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800/60 dark:bg-amber-950/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-xs text-amber-800 dark:text-amber-300">
                                <strong>{{ __('System category') }}</strong>
                                {{ __('— behavior flags are locked to prevent breaking existing products and orders. Contact your developer to change these.') }}
                            </p>
                        </div>
                    @endif

                    {{-- Behavior flags rows --}}
                    <div class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">

                        @php
                            $behaviorFlags = [
                                ['field' => 'has_ar_support',           'label' => __('AR try-on enabled'),          'desc' => __('Customers can virtually try on products in this category via the mobile app.')],
                                ['field' => 'requires_prescription',    'label' => __('Requires prescription (Rx)'), 'desc' => __('Orders for products in this category must be linked to a patient prescription at checkout.')],
                                ['field' => 'requires_expiry_tracking', 'label' => __('Expiry tracking'),           'desc' => __('Inventory items in this category must have an expiry date (e.g. contact lens solutions).')],
                            ];
                        @endphp

                        @foreach($behaviorFlags as $flag)
                            <div class="flex items-start justify-between gap-4 px-4 py-3.5">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium {{ $is_system ? 'text-zinc-500 dark:text-zinc-500' : 'text-zinc-800 dark:text-zinc-200' }}">
                                        {{ $flag['label'] }}
                                    </p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $flag['desc'] }}</p>
                                </div>
                                {{-- Toggle switch --}}
                                <button
                                    type="button"
                                    @if(!$is_system) wire:click="$toggle('{{ $flag['field'] }}')" @endif
                                    @disabled($is_system)
                                    aria-checked="{{ $this->{$flag['field']} ? 'true' : 'false' }}"
                                    role="switch"
                                    @class([
                                        'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200',
                                        'focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900',
                                        'bg-sky-600'                      => $this->{$flag['field']},
                                        'bg-zinc-300 dark:bg-zinc-600'    => !$this->{$flag['field']},
                                        'cursor-not-allowed opacity-40'   => $is_system,
                                    ])
                                >
                                    <span @class([
                                        'inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform duration-200',
                                        'translate-x-4'   => $this->{$flag['field']},
                                        'translate-x-0.5' => !$this->{$flag['field']},
                                    ])></span>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    {{-- Variant fields sub-section --}}
                    <div class="space-y-1">
                        <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Variant fields') }}</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Check which fields appear in the Add Product form for variants in this category.') }}
                        </p>
                    </div>

                    <div class="divide-y divide-zinc-100 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">

                        @php
                            $variantFlags = [
                                ['field' => 'has_color',       'label' => __('Color / finish'),      'desc' => __('Show a color picker or text field for variant color.')],
                                ['field' => 'has_frame_size',  'label' => __('Frame size'),          'desc' => __('Show a frame size selector (in mm) for variant entries.')],
                                ['field' => 'has_material',    'label' => __('Material'),            'desc' => __('Show a material dropdown (e.g. Titanium, Acetate, Metal).')],
                                ['field' => 'has_lens_type',   'label' => __('Lens type'),           'desc' => __('Show a lens type selector (e.g. Clear, Photochromic, Blue light filter).')],
                                ['field' => 'has_power_field', 'label' => __('Power / Rx field'),   'desc' => __('Show optical power (e.g. –1.00, –2.50) for contact lens variants.')],
                                ['field' => 'has_duration',    'label' => __('Duration / pack size'), 'desc' => __('Show wear duration and pack size fields for contact lenses.')],
                            ];
                        @endphp

                        @foreach($variantFlags as $flag)
                            <div class="flex items-start justify-between gap-4 px-4 py-3.5">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium {{ $is_system ? 'text-zinc-500 dark:text-zinc-500' : 'text-zinc-800 dark:text-zinc-200' }}">
                                        {{ $flag['label'] }}
                                    </p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ $flag['desc'] }}</p>
                                </div>
                                <button
                                    type="button"
                                    @if(!$is_system) wire:click="$toggle('{{ $flag['field'] }}')" @endif
                                    @disabled($is_system)
                                    aria-checked="{{ $this->{$flag['field']} ? 'true' : 'false' }}"
                                    role="switch"
                                    @class([
                                        'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200',
                                        'focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900',
                                        'bg-sky-600'                      => $this->{$flag['field']},
                                        'bg-zinc-300 dark:bg-zinc-600'    => !$this->{$flag['field']},
                                        'cursor-not-allowed opacity-40'   => $is_system,
                                    ])
                                >
                                    <span @class([
                                        'inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform duration-200',
                                        'translate-x-4'   => $this->{$flag['field']},
                                        'translate-x-0.5' => !$this->{$flag['field']},
                                    ])></span>
                                </button>
                            </div>
                        @endforeach
                    </div>

                </div>{{-- end form body --}}
            @endif

        </div>{{-- end scrollable body --}}

        {{-- ── Panel footer (sticky) ───────────────────────────────────────── --}}
        <div class="shrink-0 border-t border-zinc-200 px-5 py-4 dark:border-zinc-700">

            @if($showDeleteConfirm)

                @if($editingProductsCount === 0)
                    {{-- Can delete: Cancel + Yes delete --}}
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            wire:click="cancelDelete"
                            class="inline-flex h-9 items-center rounded-md border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            wire:click="delete"
                            wire:loading.attr="disabled"
                            wire:target="delete"
                            class="inline-flex h-9 items-center gap-2 rounded-md bg-red-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-red-700 disabled:opacity-60 dark:bg-red-700 dark:hover:bg-red-600"
                        >
                            <span wire:loading.remove wire:target="delete">{{ __('Yes, delete') }}</span>
                            <span wire:loading wire:target="delete" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ __('Deleting…') }}
                            </span>
                        </button>
                    </div>
                @else
                    {{-- Has products: only Got it --}}
                    <div class="flex justify-end">
                        <button
                            type="button"
                            wire:click="cancelDelete"
                            class="inline-flex h-9 items-center rounded-md border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        >
                            {{ __('Got it') }}
                        </button>
                    </div>
                @endif

            @else
                {{-- Normal form footer --}}
                <div class="flex items-center {{ ($mode === 'edit' && !$is_system) ? 'justify-between' : 'justify-end' }} gap-2">

                    {{-- Delete button (edit mode, custom category only) --}}
                    @if($mode === 'edit' && !$is_system)
                        <button
                            type="button"
                            wire:click="confirmDelete"
                            class="inline-flex h-9 items-center rounded-md border border-red-300 bg-white px-4 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 dark:border-red-700 dark:bg-transparent dark:text-red-400 dark:hover:bg-red-950/40"
                        >
                            {{ __('Delete') }}
                        </button>
                    @endif

                    {{-- Cancel + Save --}}
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="closePanel"
                            class="inline-flex h-9 items-center rounded-md border border-zinc-300 bg-white px-4 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="inline-flex h-9 items-center gap-2 rounded-md bg-sky-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 disabled:opacity-60 dark:bg-sky-600 dark:hover:bg-sky-500"
                        >
                            <span wire:loading.remove wire:target="save">
                                {{ $mode === 'add' ? __('Save category') : __('Save changes') }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ __('Saving…') }}
                            </span>
                        </button>
                    </div>
                </div>
            @endif

        </div>{{-- end footer --}}

    </div>{{-- end slide-over panel --}}

    {{-- ── Toast container ─────────────────────────────────────────────────── --}}
    <div
        id="toast-container"
        aria-live="polite"
        aria-atomic="true"
        class="pointer-events-none fixed bottom-5 right-5 z-[60] flex flex-col items-end gap-2"
    ></div>

</div>{{-- end component root --}}

@script
<script>
    function showToast(message, type) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');

        // Use inline styles to avoid Tailwind purge concerns on dynamic classes
        const bgColor   = type === 'error' ? '#dc2626' : (type === 'info' ? '#0284c7' : '#059669');
        toast.style.cssText = `
            background:${bgColor};color:#fff;display:flex;align-items:center;gap:12px;
            border-radius:12px;padding:10px 16px;font-size:14px;font-weight:500;
            box-shadow:0 4px 12px rgba(0,0,0,.15);transform:translateY(8px);
            opacity:0;transition:opacity .25s ease,transform .25s ease;
            pointer-events:auto;max-width:360px;
        `;

        const successIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/></svg>`;
        const errorIcon  = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="flex-shrink:0"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>`;

        toast.innerHTML = (type === 'success' ? successIcon : errorIcon) +
            `<span>${message}</span>`;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                toast.style.opacity   = '1';
                toast.style.transform = 'translateY(0)';
            });
        });

        // Auto-dismiss after 4 s
        setTimeout(() => {
            toast.style.opacity   = '0';
            toast.style.transform = 'translateY(8px)';
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, 4000);
    }

    $wire.on('toast', ({ message, type }) => {
        showToast(message, type ?? 'success');
    });
</script>
@endscript
