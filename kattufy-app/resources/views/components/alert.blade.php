@props(['tone' => 'info', 'title' => null, 'icon' => null, 'dismissible' => false])
@php
    $styles = [
        'info' => ['bg-info-soft border-line text-info-text', 'info', 'text-fg-subtle'],
        'success' => ['bg-success-soft border-forest/25 text-success-text', 'check-circle', 'text-forest dark:text-forest-300'],
        'warning' => ['bg-accent-soft border-amber/40 text-fg', 'alert', 'text-amber-700 dark:text-amber'],
        'danger' => ['bg-danger-soft border-danger/25 text-danger-text', 'x-circle', 'text-danger dark:text-danger-300'],
        'demo' => ['bg-accent-soft border-amber/50 text-fg', 'sparkle', 'text-amber-700 dark:text-amber'],
    ][$tone] ?? ['bg-info-soft border-line text-info-text', 'info', 'text-fg-subtle'];
@endphp
<div x-data="{ shown: true }" x-show="shown" x-transition.opacity
     {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-panel border px-4 py-3.5 text-[0.95rem] leading-relaxed {$styles[0]}"]) }}>
    <x-ki :name="$icon ?? $styles[1]" class="mt-0.5 size-5 {{ $styles[2] }}" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold text-fg">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" @click="shown = false" class="-m-1 rounded-md p-1 opacity-60 hover:opacity-100">
            <span class="sr-only">{{ __('ui.dismiss') }}</span>
            <x-ki name="x" class="size-4" />
        </button>
    @endif
</div>
