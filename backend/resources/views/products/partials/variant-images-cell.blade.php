{{--
    $idx (int), $existingImages (array<int, array{id:int, url:string}>)
--}}
@php
    $existingImages = $existingImages ?? [];
@endphp
<td class="variant-images-cell px-2 py-2 align-top min-w-[12rem] max-w-[15rem]">
    @if(count($existingImages) > 0)
        <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            {{ __('Saved photos') }}
        </p>
        <div class="mb-2 flex flex-wrap gap-1.5">
            @foreach($existingImages as $img)
                <label class="group relative block h-14 w-14 shrink-0 cursor-pointer overflow-hidden rounded-md border border-zinc-200 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800">
                    <img src="{{ $img['url'] }}" alt="" class="h-full w-full object-cover">
                    <input
                        type="checkbox"
                        name="variants[{{ $idx }}][remove_image_ids][]"
                        value="{{ $img['id'] }}"
                        class="peer sr-only"
                    >
                    <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/55 text-[9px] font-semibold text-white opacity-0 transition peer-checked:opacity-100">
                        {{ __('Remove') }}
                    </span>
                </label>
            @endforeach
        </div>
        <p class="mb-1.5 text-[10px] text-zinc-500 dark:text-zinc-400">{{ __('Tap a photo to mark it for removal when you save.') }}</p>
    @endif

    <label class="flex cursor-pointer flex-col gap-1 rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-2.5 py-2 transition hover:border-sky-400 hover:bg-sky-50/60 dark:border-zinc-600 dark:bg-zinc-900/50 dark:hover:border-sky-500 dark:hover:bg-sky-950/30">
        <span class="text-xs font-semibold text-sky-700 dark:text-sky-400">{{ __('Add photos') }}</span>
        <span class="text-[10px] leading-snug text-zinc-500 dark:text-zinc-400">
            {{ __('Choose several files at once. JPG, PNG, WebP or GIF — up to 4 MB each.') }}
        </span>
        <input
            type="file"
            name="variants[{{ $idx }}][images][]"
            accept="image/jpeg,image/png,image/webp,image/gif"
            multiple
            class="sr-only variant-images-input"
        >
    </label>
    <div class="variant-images-preview-wrap mt-2 hidden">
        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Preview') }}</p>
        <div class="variant-images-preview flex flex-wrap gap-1.5" aria-live="polite"></div>
    </div>

    @error('variants.'.$idx.'.images')
        <p class="mt-1 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</td>
