@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="EYECARE" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-white">
            <img src="{{ asset('images/logo.jpg') }}" alt="EYECARE logo" class="h-8 w-8 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="EYECARE" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-white">
            <img src="{{ asset('images/logo.jpg') }}" alt="EYECARE logo" class="h-8 w-8 object-contain" />
        </x-slot>
    </flux:brand>
@endif
