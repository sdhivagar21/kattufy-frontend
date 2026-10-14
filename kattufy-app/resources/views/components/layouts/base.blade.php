@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'bodyClass' => '',
])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | Kattufy' : 'Kattufy — '.config('kattufy.brand.slogan') }}</title>
    <meta name="description" content="{{ $description ?? __('ui.meta_description') }}">
    @if ($noindex || auth()->check())
        <meta name="robots" content="noindex, nofollow">
    @endif
    <meta name="theme-color" content="#202B33">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate" hreflang="en" href="{{ \App\Support\Localized::switchUrl('en') }}">
    <link rel="alternate" hreflang="ta" href="{{ \App\Support\Localized::switchUrl('ta') }}">
    <link rel="alternate" hreflang="x-default" href="{{ \App\Support\Localized::switchUrl('en') }}">

    {{-- Apply the saved theme before the first paint so the page never flashes the wrong one. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                var dark = t === 'dark' || ((!t || t === 'system') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body {{ $attributes->merge(['class' => 'min-h-full flex flex-col '.$bodyClass]) }}>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[200] focus:rounded-control focus:bg-amber focus:px-4 focus:py-2.5 focus:font-semibold focus:text-ink">
        {{ __('ui.skip_to_content') }}
    </a>
    {{ $slot }}
</body>
</html>
