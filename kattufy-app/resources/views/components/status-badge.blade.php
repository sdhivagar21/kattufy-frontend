@props(['state' => null, 'tone' => null, 'label' => null, 'size' => 'md'])
{{-- Verification status pill. Pass a VerificationState, or a tone + label. --}}
@php
    $tone ??= $state?->tone() ?? 'neutral';
    $label ??= $state?->label();
    [$classes, $icon] = [
        'success' => ['bg-success-soft text-success-text ring-forest/25', 'check-circle'],
        'pending' => ['bg-accent-soft text-accent-text ring-amber/40', 'clock'],
        'warning' => ['bg-accent-soft text-accent-text ring-amber/50', 'alert'],
        'danger' => ['bg-danger-soft text-danger-text ring-danger/25', 'x-circle'],
        'neutral' => ['bg-surface-2 text-fg-muted ring-line-strong', 'info'],
    ][$tone] ?? ['bg-surface-2 text-fg-muted ring-line-strong', 'info'];
    $sizes = $size === 'lg' ? 'gap-2 px-3 py-1.5 text-[0.95rem]' : 'gap-1.5 px-2.5 py-1 text-[0.8125rem]';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex max-w-full items-center rounded-full font-semibold ring-1 ring-inset $classes $sizes"]) }}>
    @if ($tone === 'pending')
        <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-amber opacity-60 motion-reduce:hidden"></span><span class="relative inline-flex size-2 rounded-full bg-amber"></span></span>
    @else
        <x-ki :name="$icon" class="size-4" />
    @endif
    <span class="truncate">{{ $label }}</span>
</span>
