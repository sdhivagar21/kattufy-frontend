@props(['tone' => 'dark'])
{{-- Switches light/dark. Saved in this browser; starts from the device setting. --}}
@php
    $classes = $tone === 'dark'
        ? 'text-white/80 hover:bg-white/10 hover:text-white'
        : 'text-fg-muted hover:bg-surface-2 hover:text-fg';
@endphp
<button type="button" x-data="themeToggle" @click="toggle()"
        {{ $attributes->merge(['class' => "relative inline-flex size-11 items-center justify-center rounded-control transition-colors $classes"]) }}
        :aria-pressed="dark.toString()"
        :title="dark ? @js(__('ui.theme_light')) : @js(__('ui.theme_dark'))">
    <span class="sr-only" x-text="dark ? @js(__('ui.theme_light')) : @js(__('ui.theme_dark'))">{{ __('ui.theme_toggle') }}</span>
    <x-ki name="moon" class="size-5 transition-all duration-300 dark:scale-0 dark:-rotate-90 dark:opacity-0" />
    <x-ki name="sun" class="absolute size-5 scale-0 rotate-90 opacity-0 transition-all duration-300 dark:scale-100 dark:rotate-0 dark:opacity-100" />
</button>
