<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#A9D7FF] antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-10 md:px-8">
            <div class="w-full max-w-7xl">
                <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[minmax(0,520px)_minmax(0,1fr)]">
                    <div class="flex items-center justify-center px-4 py-6 sm:px-8 sm:py-10">
                        <div class="w-full max-w-lg">
                            <div class="flex flex-col gap-6">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>

                    <div class="hidden h-full items-center justify-center md:flex">
                        <div class="relative flex h-[360px] w-full max-w-xl items-center justify-center">
                            <div class="absolute inset-0 rounded-3xl bg-[#A9D7FF]"></div>

                            <div class="relative flex h-full w-full items-center justify-center gap-5">
                                <div class="h-[320px] w-[230px] overflow-hidden rounded-2xl shadow-[0_25px_60px_rgba(0,0,0,0.3)] transition-transform duration-300 ease-out hover:scale-[1.06]">
                                    <img
                                        src="{{ asset('images/eyeglass with human.jpg') }}"
                                        alt="Person wearing eyeglasses"
                                        class="h-full w-full object-cover"
                                    />
                                </div>

                                <div class="flex h-full flex-col justify-between gap-5">
                                    <div class="h-[170px] w-[260px] overflow-hidden rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.28)] transition-transform duration-300 ease-out hover:scale-[1.06]">
                                        <img
                                            src="{{ asset('images/eyeglass.jpg') }}"
                                            alt="Close-up of eyeglasses"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>

                                    <div class="h-[170px] w-[260px] overflow-hidden rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] transition-transform duration-300 ease-out hover:scale-[1.06]">
                                        <img
                                            src="{{ asset('images/eyeglass2.jpg') }}"
                                            alt="Eyeglass frame"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
