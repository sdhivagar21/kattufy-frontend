@use('App\Support\ListingPlans')
@use('App\Modules\Providers\Enums\ProviderStatus')
{{--
    Provider overview. Four separate statuses are kept apart on purpose: the
    selected listing plan (this browser only, not active), plan activation
    (payment not connected), verification (server) and public listing (server).
--}}
<x-layouts.dashboard :title="__('provider.dashboard_title')" area="provider">
    @php
        $reference = 'KTF-P-'.str_pad($provider->id, 4, '0', STR_PAD_LEFT);
        $category = $provider->primaryCategory();
        $published = $provider->status === ProviderStatus::Published;
        $publicUrl = $category && $published ? lroute('directory.child', [$category->slug, $provider->slug]) : null;
        $planNames = collect(ListingPlans::PLANS)->mapWithKeys(fn ($p) => [$p => __('plans.'.$p.'.name')])->all();
        $completion = (int) $provider->completeness_score;
    @endphp

    <div x-data="planPicker({ key: @js(ListingPlans::storageKey($provider)) })" class="space-y-8 sm:space-y-10">
        {{-- Business header --}}
        <section class="relative overflow-hidden rounded-panel bg-ink text-white shadow-raised animate-fade-up dark:bg-ink-900">
            <div class="bg-blueprint absolute inset-0" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -top-24 -right-20 size-72 rounded-full bg-amber/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:p-8">
                <span class="flex size-16 shrink-0 items-center justify-center rounded-panel bg-amber text-2xl font-bold text-ink" style="font-stretch:116%" lang="en">{{ $provider->initials() }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-amber">{{ __('provider.dashboard_title') }}</p>
                    <h1 class="type-display mt-1 text-[1.7rem] text-white sm:text-[2.1rem] ta:text-[1.45rem]" style="overflow-wrap:anywhere">{{ $provider->business_name_en }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/75">
                        <x-status-badge :state="$state" class="bg-white/95 dark:bg-white/10" />
                        @if ($provider->published_at)<span>{{ __('provider.approved_on', ['date' => ist($provider->published_at, 'j F Y')]) }}</span>@endif
                        <span class="font-mono">{{ $reference }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                    <a href="{{ lroute('provider.profile') }}" class="btn btn-primary"><x-ki name="pencil" class="size-5" /> {{ __('provider.edit_profile') }}</a>
                    @if ($publicUrl)
                        <a href="{{ $publicUrl }}" class="btn btn-sm border border-white/25 text-white hover:bg-white/5"><x-ki name="external" class="size-4" /> {{ __('provider.view_public') }}</a>
                    @endif
                </div>
            </div>
        </section>

        @if ($currentFeedback)
            <x-alert tone="warning" :title="__('provider.needs_update_title')">
                <p class="font-medium text-fg">“{{ $currentFeedback->reason }}”</p>
                <p class="mt-1 text-sm">{{ __('provider.needs_update_text') }}</p>
            </x-alert>
        @endif

        {{-- At a glance: four statuses that never stand in for one another --}}
        <section aria-labelledby="glance-title">
            <h2 id="glance-title" class="sr-only">{{ __('provider.at_a_glance') }}</h2>
            <div class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 xl:grid-cols-4 [&>*]:min-w-0 [&_p]:[overflow-wrap:anywhere]">
                {{-- 1. Listing plan (selected in this browser; never "active") --}}
                <a href="{{ lroute('provider.plan') }}" class="card group flex flex-col p-4 transition-shadow hover:shadow-raised sm:p-5">
                    <span class="flex items-center justify-between gap-2">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-accent-soft text-amber-700 dark:text-amber"><x-ki name="sparkle" class="size-[1.15rem]" /></span>
                        <x-ki name="chevron-right" class="size-4 text-fg-subtle transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <p class="mt-3 text-sm text-fg-subtle">{{ __('plans.listing_plan') }}</p>
                    <p class="mt-0.5 font-semibold" x-text="plan ? @js($planNames)[plan] : @js(__('plans.no_plan'))">{{ __('plans.no_plan') }}</p>
                    <p class="mt-2 flex flex-wrap gap-1" x-cloak x-show="plan">
                        <span class="rounded-full bg-surface-2 px-2 py-0.5 text-xs font-semibold text-fg-muted">{{ __('plans.state_selected') }}</span>
                        <span class="rounded-full bg-accent-soft px-2 py-0.5 text-xs font-semibold text-accent-text">{{ __('plans.state_not_active') }}</span>
                    </p>
                    <p class="mt-2 text-xs text-fg-subtle" x-show="!plan">{{ __('plans.choose_plan') }}</p>
                </a>

                {{-- 2. Verification (server) --}}
                <a href="{{ lroute('provider.verification') }}" class="card group flex flex-col p-4 transition-shadow hover:shadow-raised sm:p-5">
                    <span class="flex items-center justify-between gap-2">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-surface-2 text-fg-muted"><x-ki name="shield" class="size-[1.15rem]" /></span>
                        <x-ki name="chevron-right" class="size-4 text-fg-subtle transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <p class="mt-3 text-sm text-fg-subtle">{{ __('provider.glance_verification') }}</p>
                    <p class="mt-1"><x-status-badge :state="$state" size="sm" /></p>
                </a>

                {{-- 3. Public listing (server) --}}
                <div class="card flex flex-col p-4 sm:p-5">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-surface-2 text-fg-muted"><x-ki name="globe" class="size-[1.15rem]" /></span>
                    <p class="mt-3 text-sm text-fg-subtle">{{ __('provider.publication') }}</p>
                    <p class="mt-0.5 flex items-center gap-2 font-semibold">
                        <span @class(['size-2 rounded-full', 'bg-forest dark:bg-forest-300' => $published, 'bg-line-strong' => ! $published]) aria-hidden="true"></span>
                        {{ $published ? __('provider.publication_live') : __('provider.publication_hidden') }}
                    </p>
                    @if ($publicUrl)
                        <a href="{{ $publicUrl }}" class="link mt-2 self-start text-xs">{{ __('provider.view_public') }}</a>
                    @endif
                </div>

                {{-- 4. Profile completion (server-calculated) --}}
                <a href="{{ lroute('provider.profile') }}" class="card group flex flex-col p-4 transition-shadow hover:shadow-raised sm:p-5">
                    <span class="flex items-center justify-between gap-2">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-surface-2 text-fg-muted"><x-ki name="building" class="size-[1.15rem]" /></span>
                        <x-ki name="chevron-right" class="size-4 text-fg-subtle transition-transform group-hover:translate-x-0.5" />
                    </span>
                    <p class="mt-3 text-sm text-fg-subtle">{{ __('provider.profile_completion') }}</p>
                    <p class="mt-0.5 font-semibold tabular-nums">{{ $completion }}%</p>
                    <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-surface-2" role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('provider.profile_completion') }}">
                        <span class="block h-full rounded-full bg-amber" style="width: {{ max(2, min(100, $completion)) }}%"></span>
                    </span>
                </a>
            </div>
            <p class="mt-4 flex items-start gap-2 text-sm text-fg-subtle"><x-ki name="info" class="mt-0.5 size-4" /> {{ __('plans.states_note') }}</p>
        </section>

        {{-- Listing plans --}}
        <section aria-labelledby="plans-title">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="max-w-2xl">
                    <h2 id="plans-title" class="type-heading text-[1.4rem] ta:text-[1.2rem]">{{ __('plans.choose_title') }}</h2>
                    <p class="mt-1 text-fg-muted">{{ __('plans.choose_intro') }}</p>
                </div>
            </div>

            <fieldset class="mt-6">
                <legend class="sr-only">{{ __('plans.plans_label') }}</legend>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">
                    @foreach (ListingPlans::PLANS as $plan)
                        @include('provider.partials.plan-card', ['plan' => $plan])
                    @endforeach
                </div>
            </fieldset>

            {{-- Selection summary --}}
            <div class="mt-6 flex flex-col gap-4 rounded-panel border border-line bg-surface p-5 shadow-card sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-live="polite">
                <div class="min-w-0">
                    <p class="font-medium text-fg-muted" x-show="!plan">{{ __('plans.select_to_continue') }}</p>
                    @foreach (ListingPlans::PLANS as $plan)
                        <div x-cloak x-show="plan === '{{ $plan }}'" :class="justChose && 'animate-fade-up'">
                            <p class="flex items-center gap-2 font-semibold"><x-ki name="check-circle" class="size-5 text-forest dark:text-forest-300" /> {{ __('plans.you_selected', ['plan' => __('plans.'.$plan.'.name')]) }}</p>
                            <p class="mt-1 text-sm text-fg-subtle">{{ collect(ListingPlans::FIELDS[$plan])->map(fn ($f) => __('plans.fields.'.$f))->join(' · ') }}</p>
                        </div>
                    @endforeach
                    <p class="mt-2 text-xs text-fg-subtle">{{ __('plans.no_prices') }}</p>
                </div>
                <a href="{{ lroute('provider.plan') }}?step=details" class="btn btn-primary btn-lg shrink-0"
                   :class="!plan && 'pointer-events-none opacity-50'" :aria-disabled="(!plan).toString()" :tabindex="plan ? 0 : -1">
                    {{ __('plans.customize') }} <x-ki name="arrow-right" class="size-5" />
                </a>
            </div>
        </section>

        {{-- Note from the verification team --}}
        @if ($feedback?->reason && ! $currentFeedback)
            <section class="card card-pad">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-fg-subtle"><x-ki name="chat" class="size-4" /> {{ __('provider.review_note') }}</h2>
                <p class="mt-2 text-lg">“{{ $feedback->reason }}”</p>
                <p class="mt-1 text-sm text-fg-subtle">{{ __('provider.feedback_when', ['date' => ist($feedback->created_at, 'j F Y')]) }}</p>
            </section>
        @endif
    </div>
</x-layouts.dashboard>
