@props([
    'caption' => true,
])

<div
    {{ $attributes->class(
        'flex h-full w-full flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-sky-100/80 via-zinc-50 to-zinc-100 dark:from-zinc-800 dark:via-zinc-900 dark:to-zinc-950',
    ) }}
    role="img"
    aria-label="{{ __('Product image placeholder') }}"
>
    {{-- Simple frame icon — fits optical / catalog context --}}
    <svg
        class="w-[42%] max-w-[10rem] text-zinc-300 dark:text-zinc-600"
        viewBox="0 0 120 72"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <path
            d="M12 36h10M98 36h10"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
        />
        <rect
            x="22"
            y="22"
            width="34"
            height="28"
            rx="9"
            stroke="currentColor"
            stroke-width="2.5"
        />
        <rect
            x="64"
            y="22"
            width="34"
            height="28"
            rx="9"
            stroke="currentColor"
            stroke-width="2.5"
        />
        <path
            d="M56 36h8"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
        />
    </svg>

    @if($caption)
        <p
            class="mt-3 max-w-[12rem] px-3 text-center text-[11px] font-medium uppercase tracking-wide text-zinc-400 dark:text-zinc-500"
        >
            {{ __('No image') }}
        </p>
    @endif
</div>
