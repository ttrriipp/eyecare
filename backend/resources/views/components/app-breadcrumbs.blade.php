@props([
    'items' => [],
])

@php
    $items = array_values($items);
@endphp

@if(count($items) > 0)
    <nav {{ $attributes->class(['text-sm']) }} aria-label="{{ __('Breadcrumb') }}">
        <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-zinc-500 dark:text-zinc-400">
            @foreach($items as $item)
                @if(! $loop->first)
                    <li aria-hidden="true" class="text-zinc-400 dark:text-zinc-500">/</li>
                @endif
                <li class="min-w-0 truncate">
                    @if(! empty($item['href']))
                        <a
                            href="{{ $item['href'] }}"
                            class="text-zinc-600 underline decoration-zinc-300 underline-offset-2 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:decoration-zinc-600 dark:hover:text-zinc-100"
                            wire:navigate
                        >
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span
                            class="font-medium text-zinc-700 dark:text-zinc-300"
                            @if($loop->last) aria-current="page" @endif
                        >
                            {{ $item['label'] }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
