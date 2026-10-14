@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'bg-blueprint-light flex flex-col items-center rounded-panel border border-dashed border-line-strong px-6 py-12 text-center']) }}>
    <span class="flex size-14 items-center justify-center rounded-2xl bg-surface text-amber-700 shadow-card ring-1 ring-line dark:text-amber">
        <x-ki :name="$icon" class="size-7" />
    </span>
    <h3 class="type-heading mt-5 text-lg">{{ $title }}</h3>
    @if ($text)
        <p class="mt-2 max-w-md text-fg-muted">{{ $text }}</p>
    @endif
    @if (isset($actions))
        <div class="mt-6 flex flex-wrap justify-center gap-3">{{ $actions }}</div>
    @endif
</div>
