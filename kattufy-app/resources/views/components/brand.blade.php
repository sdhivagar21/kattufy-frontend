@props([
    'href' => null,
    'tone' => 'dark',      // dark = on a charcoal background, light = on paper/white
    'size' => 'md',        // sm | md | lg
    'slogan' => true,
])
{{--
    Kattufy brand lock-up: the supplied logo mark, the wordmark and the exact
    slogan. Logo files are configured in config/kattufy.php (brand.*).
--}}
@php
    $logo = brand_image($tone === 'dark' ? 'logo_on_dark' : 'logo_on_light');
    $mark = ['sm' => 'size-9', 'md' => 'size-10 sm:size-11', 'lg' => 'size-16 sm:size-20'][$size];
    $word = ['sm' => 'text-[1.2rem]', 'md' => 'text-[1.3rem] sm:text-[1.45rem]', 'lg' => 'text-[2.1rem] sm:text-[2.6rem]'][$size];
    $tag = ['sm' => 'text-[0.72rem]', 'md' => 'text-[0.76rem] sm:text-[0.8rem]', 'lg' => 'text-sm sm:text-base'][$size];
    $ink = $tone === 'dark' ? 'text-white' : 'text-fg';
    $muted = $tone === 'dark' ? 'text-white/72' : 'text-fg-muted';
    $element = $href ? 'a' : 'span';
@endphp
<{{ $element }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group inline-flex items-center gap-2.5 sm:gap-3 rounded-md']) }}
    @if ($href) aria-label="Kattufy — {{ config('kattufy.brand.slogan') }} {{ __('ui.home') }}" @endif>
    @if ($logo)
        <img src="{{ $logo }}" alt="" width="80" height="80"
             class="{{ $mark }} shrink-0 object-contain transition-transform duration-300 ease-out group-hover:-rotate-3">
    @else
        {{-- Placeholder until the logo file is added (see README, "Brand images"). --}}
        <span class="{{ $mark }} flex shrink-0 items-center justify-center rounded-lg bg-amber font-bold text-ink" title="Logo placeholder">K</span>
    @endif
    <span class="flex min-w-0 flex-col leading-none">
        <span class="{{ $word }} {{ $ink }} font-bold tracking-tight" style="font-stretch: 112%" lang="en">Kattu<span class="text-amber">fy</span></span>
        @if ($slogan)
            <span class="{{ $tag }} {{ $muted }} mt-1 font-medium tracking-[0.01em]" lang="en">{{ config('kattufy.brand.slogan') }}</span>
        @endif
    </span>
</{{ $element }}>
