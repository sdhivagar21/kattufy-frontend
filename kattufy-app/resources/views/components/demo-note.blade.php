@props(['code' => null])
{{-- Shown only in demo mode, never in production. Never claims that an SMS or email was sent. --}}
@if (config('kattufy.demo_mode') && ! app()->isProduction())
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-control border border-dashed border-amber/60 bg-accent-soft px-3.5 py-2.5 text-sm text-fg']) }}>
        <x-ki name="sparkle" class="mt-0.5 size-4 text-amber-700 dark:text-amber" />
        <p>
            <span class="font-semibold">{{ __('ui.demo_no_otp') }}</span>
            @if ($code)
                {!! __('ui.demo_use_code', ['code' => '<code class="rounded bg-surface px-1.5 py-0.5 font-mono font-bold tracking-widest text-fg ring-1 ring-line">'.e($code).'</code>']) !!}
            @endif
            {{ $slot }}
        </p>
    </div>
@endif
