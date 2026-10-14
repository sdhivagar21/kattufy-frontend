{{--
    Step 1: brand splash. The supplied splash image already carries the
    wordmark and slogan, so the slogan is not repeated on screen (it is the
    image's alt text). Moves on to login after a short pause; tap, click or any
    key skips. Without JavaScript the meta refresh does the same.
--}}
<x-layouts.base :title="null" noindex body-class="bg-ink">
    <x-slot:head>
        <meta http-equiv="refresh" content="4;url={{ $next }}">
    </x-slot:head>

    <main id="main" x-data="splash({ next: @js($next), duration: {{ (int) config('kattufy.brand.splash_duration_ms') }} })"
          @click="go()" @keydown.window="go()"
          :class="leaving && 'opacity-0'"
          class="relative flex min-h-dvh flex-1 cursor-pointer flex-col items-center justify-center overflow-hidden bg-ink px-4 text-white transition-opacity duration-300 dark:bg-ink-950">
        <div class="bg-blueprint absolute inset-0 opacity-70" aria-hidden="true"></div>
        <div class="absolute top-1/2 left-1/2 size-[42rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber/[0.07] blur-3xl" aria-hidden="true"></div>

        <h1 class="sr-only">Kattufy — {{ config('kattufy.brand.slogan') }}</h1>

        <div class="relative flex w-full flex-col items-center text-center">
            @if ($image)
                <picture class="block animate-fade-in">
                    @if ($portrait)
                        <source media="(max-aspect-ratio: 4/5)" srcset="{{ $portrait }}">
                    @endif
                    <img src="{{ $image }}" alt="Kattufy — {{ config('kattufy.brand.slogan') }}" width="1600" height="800"
                         fetchpriority="high"
                         class="block h-auto max-h-[72vh] w-[min(92vw,48rem)] object-contain drop-shadow-[0_20px_50px_rgba(0,0,0,0.45)]">
                </picture>
            @else
                {{-- Placeholder: add public/images/kattufy-splash.jpg (see README). --}}
                <div class="flex animate-fade-in flex-col items-center" title="Splash image placeholder">
                    <x-brand tone="dark" size="lg" />
                </div>
            @endif

            <span class="mt-10 block h-1 w-44 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                <span class="block h-full w-full origin-left animate-bar rounded-full bg-amber" style="animation-duration: {{ (int) config('kattufy.brand.splash_duration_ms') }}ms"></span>
            </span>
            <p class="mt-5 text-sm text-white/55">{{ __('ui.splash_skip') }}</p>
        </div>

        <noscript>
            <p class="relative mt-6"><a class="link text-white" href="{{ $next }}">{{ __('ui.continue') }}</a></p>
        </noscript>
    </main>
</x-layouts.base>
