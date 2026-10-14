@props(['tone' => 'dark', 'full' => false])
{{-- Links to this same page in the other language (/ta/… ↔ /…). --}}
@php
    $tamil = app()->getLocale() === 'ta';
    $target = $tamil ? 'en' : 'ta';
    $classes = $tone === 'dark'
        ? 'border-white/20 text-white hover:border-amber hover:bg-white/5'
        : 'border-line-strong text-fg hover:border-fg-subtle hover:bg-surface-2';
@endphp
<a href="{{ \App\Support\Localized::switchUrl($target) }}" hreflang="{{ $target }}" lang="{{ $target }}"
   {{ $attributes->merge(['class' => "inline-flex min-h-11 items-center gap-2 rounded-control border px-3 text-sm font-semibold transition-colors $classes"]) }}
   title="{{ $tamil ? 'View in English' : 'தமிழில் பார்க்க' }}">
    <x-ki name="language" class="size-[1.1rem] opacity-80" />
    @if ($full)
        <span>{{ $tamil ? 'English' : 'தமிழில் பார்க்க' }}</span>
    @else
        <span>{{ $tamil ? 'English' : 'தமிழ்' }}</span>
    @endif
</a>
