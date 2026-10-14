{{--
    One listing plan as a selectable card (a radio in the "Listing plans"
    group). Needs an Alpine scope with `plan`, `choose(plan)` and `justChose`
    (planPicker or listingPlanner). No prices: none are configured.
--}}
@php
    $look = [
        'free' => [
            'icon' => 'document',
            'tile' => 'bg-slate-100 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600/50',
            'tagline' => 'text-fg-muted',
            'includes' => 'text-fg-subtle',
            'check' => 'text-slate-500 dark:text-slate-400',
            'rule' => 'border-line',
            'button' => 'btn-secondary',
            'buttonOn' => 'btn-dark',
            'mark' => 'border-slate-300 dark:border-slate-600',
        ],
        'pro' => [
            'icon' => 'briefcase',
            'tile' => 'bg-white/10 text-amber ring-1 ring-white/15',
            'tagline' => 'text-pro-100/80',
            'includes' => 'text-amber/90',
            'check' => 'text-amber',
            'rule' => 'border-white/12',
            'button' => 'border border-amber/60 text-amber hover:bg-amber hover:text-ink',
            'buttonOn' => 'bg-amber text-ink',
            'mark' => 'border-white/30',
        ],
        'premium' => [
            'icon' => 'star',
            'tile' => 'bg-amber text-ink shadow-[0_6px_18px_-6px_rgb(242_169_0/0.6)]',
            'tagline' => 'text-white/72',
            'includes' => 'text-amber',
            'check' => 'text-amber',
            'rule' => 'border-amber/20',
            'button' => 'btn-primary',
            'buttonOn' => 'bg-white text-ink',
            'mark' => 'border-white/30',
        ],
    ][$plan];
@endphp
<label class="plan-card plan-card-{{ $plan }} cursor-pointer" :class="plan === '{{ $plan }}' && 'is-selected'" data-plan="{{ $plan }}">
    <input type="radio" name="listing-plan" value="{{ $plan }}" class="sr-only"
           :checked="plan === '{{ $plan }}'" @change="choose('{{ $plan }}')"
           aria-labelledby="plan-{{ $plan }}-name" aria-describedby="plan-{{ $plan }}-tagline">

    <div class="flex items-start justify-between gap-3">
        <span class="flex size-11 items-center justify-center rounded-xl {{ $look['tile'] }}"><x-ki :name="$look['icon']" class="size-5" /></span>
        <span class="flex items-center gap-2">
            @if ($plan === 'premium')
                <span class="plan-badge-sheen rounded-full px-2.5 py-1 text-[0.7rem] font-bold tracking-wide text-ink uppercase ta:normal-case">{{ __('plans.premium_badge') }}</span>
            @endif
            {{-- Selected checkmark --}}
            <span class="flex size-7 items-center justify-center rounded-full border-2 transition-colors duration-200"
                  :class="plan === '{{ $plan }}' ? 'border-amber bg-amber text-ink' : '{{ $look['mark'] }} text-transparent'" aria-hidden="true">
                <x-ki name="check" class="size-4" ::class="plan === '{{ $plan }}' && justChose && 'animate-pop'" />
            </span>
        </span>
    </div>

    <h3 id="plan-{{ $plan }}-name" class="type-heading mt-5 text-[1.3rem] {{ $plan === 'free' ? '' : 'text-white' }}">{{ __('plans.'.$plan.'.name') }}</h3>
    <p id="plan-{{ $plan }}-tagline" class="mt-1 text-[0.95rem] {{ $look['tagline'] }}">{{ __('plans.'.$plan.'.tagline') }}</p>

    <div class="mt-5 mb-6 border-t pt-4 {{ $look['rule'] }}">
        <p class="text-xs font-semibold tracking-[0.08em] uppercase ta:text-[0.8rem] ta:tracking-normal ta:normal-case {{ $look['includes'] }}">{{ __('plans.'.$plan.'.includes') }}</p>
        <ul class="mt-3 space-y-2.5 text-[0.95rem]">
            @foreach (__('plans.'.$plan.'.features') as $feature)
                <li class="flex items-start gap-2.5"><x-ki name="check-circle" class="mt-0.5 size-[1.1rem] {{ $look['check'] }}" /> <span>{{ $feature }}</span></li>
            @endforeach
        </ul>
    </div>

    <span class="btn mt-auto w-full"
          :class="plan === '{{ $plan }}' ? '{{ $look['buttonOn'] }}' : '{{ $look['button'] }}'" aria-hidden="true">
        <span x-show="plan !== '{{ $plan }}'">{{ __('plans.select') }}</span>
        <span x-cloak x-show="plan === '{{ $plan }}'" class="inline-flex items-center gap-2"><x-ki name="check-circle" class="size-5" /> {{ __('plans.selected') }}</span>
    </span>
</label>
