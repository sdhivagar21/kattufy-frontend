@props(['steps' => [], 'current' => 1])
{{-- Progress through a short flow, e.g. Your details → Verify mobile → Choose a role. --}}
<ol {{ $attributes->merge(['class' => 'flex items-center gap-2']) }} aria-label="{{ __('ui.progress') }}">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; @endphp
        <li class="flex min-w-0 flex-1 items-center gap-2" @if ($n === $current) aria-current="step" @endif>
            <span @class([
                'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors',
                'bg-forest text-white' => $n < $current,
                'bg-amber text-ink ring-4 ring-amber/20' => $n === $current,
                'bg-surface-2 text-fg-subtle ring-1 ring-line-strong' => $n > $current,
            ])>
                @if ($n < $current)
                    <x-ki name="check" class="size-4" />
                    <span class="sr-only">{{ __('ui.done') }}:</span>
                @else
                    {{ $n }}
                @endif
            </span>
            <span @class(['truncate text-[0.8rem] font-medium sm:text-sm', 'text-fg' => $n === $current, 'text-fg-subtle' => $n !== $current])>{{ $label }}</span>
            @if (! $loop->last)
                <span @class(['h-px flex-1', 'bg-forest' => $n < $current, 'bg-line-strong' => $n >= $current]) aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
