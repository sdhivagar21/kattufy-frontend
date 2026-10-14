@use('App\Support\ListingPlans')
{{--
    Listing plan: Choose plan → Plan details (only that plan's fields) →
    Review and save. Front end only: the plan and its details are kept in
    this browser and clearly shown as not active. No prices (none are set),
    no payment, no activation and no change to verification or publication.
--}}
@php
    $steps = ['choose' => __('plans.step_choose'), 'details' => __('plans.step_details'), 'review' => __('plans.step_review')];
    $sectionMeta = [
        'company' => ['building', __('plans.section_company')],
        'contact' => ['map-pin', __('plans.section_contact')],
        'portfolio' => ['briefcase', __('plans.section_portfolio')],
        'media' => ['photo', __('plans.section_media')],
    ];
    $planIcon = ['free' => 'document', 'pro' => 'briefcase', 'premium' => 'star'];
    $planTile = "{ 'bg-slate-100 text-slate-600 dark:bg-slate-700/40 dark:text-slate-300': plan === 'free', 'bg-pro-800 text-amber': plan === 'pro', 'bg-amber text-ink': plan === 'premium' }";
    $countChoices = fn (string $key) => [trans_choice($key, 0), trans_choice($key, 1), trans_choice($key, 2, ['count' => ':count'])];
@endphp
<x-layouts.dashboard :title="__('plans.choose_title')" area="provider">
    <div @include('provider.partials.planner-data', ['mode' => 'wizard']) class="animate-fade-up">

        {{-- Heading and progress --}}
        <header class="mb-7 sm:mb-8">
            <p class="eyebrow">{{ __('plans.listing_plan') }}</p>
            <h1 class="type-display mt-2 text-[1.75rem] sm:text-[2.2rem] ta:text-[1.45rem] sm:ta:text-[1.75rem]" x-text="title">{{ __('plans.choose_title') }}</h1>
            <p class="mt-3 max-w-3xl text-fg-muted" x-show="step === 'choose'">{{ __('plans.choose_intro') }}</p>
            <p class="mt-3 max-w-3xl text-fg-muted" x-cloak x-show="step === 'details'">{{ __('plans.details_intro') }}</p>
            <p class="mt-3 max-w-3xl text-fg-muted" x-cloak x-show="step === 'review'">{{ __('plans.review_intro') }}</p>

            <ol class="mt-6 flex max-w-2xl items-center gap-2" aria-label="{{ __('plans.progress') }}">
                @foreach ($steps as $key => $label)
                    <li class="flex min-w-0 flex-1 items-center gap-2" :aria-current="step === '{{ $key }}' ? 'step' : null">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors duration-300"
                              :class="stepIndex > {{ $loop->index }} ? 'bg-forest text-white' : (stepIndex === {{ $loop->index }} ? 'bg-amber text-ink ring-4 ring-amber/20' : 'bg-surface-2 text-fg-subtle ring-1 ring-line-strong')">
                            <x-ki name="check" class="size-4" x-show="stepIndex > {{ $loop->index }}" />
                            <span x-show="stepIndex <= {{ $loop->index }}">{{ $loop->iteration }}</span>
                        </span>
                        <span class="truncate text-[0.8rem] font-medium sm:text-sm" :class="stepIndex === {{ $loop->index }} ? 'text-fg' : 'text-fg-subtle'">{{ $label }}</span>
                        @unless ($loop->last)
                            <span class="h-px flex-1 transition-colors" :class="stepIndex > {{ $loop->index }} ? 'bg-forest' : 'bg-line-strong'" aria-hidden="true"></span>
                        @endunless
                    </li>
                @endforeach
            </ol>
            <p class="mt-2.5 flex items-center gap-1.5 text-xs text-fg-subtle"><x-ki name="lock" class="size-3.5" /> {{ __('plans.step_later') }}</p>
        </header>

        <p x-cloak x-show="notice" x-text="notice" class="mb-5 flex items-center gap-2 rounded-control bg-surface-2 px-4 py-3 text-sm font-medium" role="status"></p>

        {{-- ============ Step 1: choose a plan ============ --}}
        <section x-show="step === 'choose'" aria-labelledby="choose-legend">
            <fieldset>
                <legend id="choose-legend" class="sr-only">{{ __('plans.plans_label') }}</legend>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">
                    @foreach (ListingPlans::PLANS as $plan)
                        @include('provider.partials.plan-card', ['plan' => $plan])
                    @endforeach
                </div>
            </fieldset>

            {{-- Selection summary --}}
            <div class="mt-7 rounded-panel border border-line bg-surface p-5 shadow-card sm:p-6" aria-live="polite">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-fg-muted" x-show="!plan">{{ __('plans.select_to_continue') }}</p>
                        @foreach (ListingPlans::PLANS as $plan)
                            <div x-cloak x-show="plan === '{{ $plan }}'" :class="justChose && 'animate-fade-up'">
                                <p class="flex items-center gap-2 text-lg font-semibold"><x-ki name="check-circle" class="size-5 text-forest dark:text-forest-300" /> {{ __('plans.you_selected', ['plan' => __('plans.'.$plan.'.name')]) }}</p>
                                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="{{ __('plans.'.$plan.'.name') }}">
                                    @foreach (ListingPlans::FIELDS[$plan] as $field)
                                        <li class="inline-flex items-center gap-1 rounded-tag bg-surface-2 px-2 py-1 text-xs font-medium text-fg-muted">
                                            <x-ki name="check" class="size-3.5 text-forest dark:text-forest-300" />
                                            {{ __('plans.fields.'.$field) }}@if ($field === 'description') · {{ number_format(ListingPlans::DESCRIPTION_LIMITS[$plan]) }}@endif
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-3 flex flex-wrap items-center gap-1.5">
                                    <x-status-badge tone="neutral" size="sm" :label="__('plans.state_selected')" />
                                    <x-status-badge tone="pending" size="sm" :label="__('plans.state_not_active')" />
                                    <x-status-badge tone="neutral" size="sm" :label="__('plans.state_payment')" />
                                </p>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-primary btn-lg shrink-0" @click="startDetails()" :disabled="!plan">
                        {{ __('plans.customize') }} <x-ki name="arrow-right" class="size-5" />
                    </button>
                </div>
            </div>

            <x-alert tone="info" class="mt-5">{{ __('plans.states_note') }} {{ __('plans.no_prices') }}</x-alert>
        </section>

        {{-- ============ Step 2: plan details ============ --}}
        <section x-cloak x-show="step === 'details'">
            <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_21rem]">
                <div class="min-w-0">
                    {{-- Plan bar --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-panel border border-line bg-surface px-4 py-3 shadow-card">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl" :class="{{ $planTile }}">
                                @foreach ($planIcon as $p => $icon)<x-ki :name="$icon" class="size-5" x-show="plan === '{{ $p }}'" />@endforeach
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold" x-text="planName()"></p>
                                <p class="flex flex-wrap gap-x-2 text-xs text-fg-subtle"><span>{{ __('plans.state_selected') }}</span><span aria-hidden="true">·</span><span class="font-semibold text-accent-text">{{ __('plans.state_not_active') }}</span></p>
                            </div>
                        </div>
                        <button type="button" class="link text-sm" @click="step = 'choose'; syncUrl(); scrollTop()">{{ __('plans.change_plan') }}</button>
                    </div>

                    {{-- Section switcher (Pro and Premium): ordinary buttons, all reachable with Tab --}}
                    <nav x-show="sections.length > 1" class="mt-5 -mx-1 overflow-x-auto px-1 pb-1 [scrollbar-width:none]" aria-label="{{ __('plans.sections') }}">
                        <div class="inline-flex min-w-full gap-1 rounded-full border border-line bg-surface-2 p-1">
                            @foreach ($sectionMeta as $key => [$icon, $label])
                                <button type="button" data-section-tab="{{ $key }}" x-show="sections.includes('{{ $key }}')" @click="goSection('{{ $key }}')"
                                        :aria-current="section === '{{ $key }}' ? 'step' : null"
                                        class="relative inline-flex min-h-10 flex-1 shrink-0 items-center justify-center gap-2 rounded-full px-4 text-sm font-semibold whitespace-nowrap transition-colors"
                                        :class="section === '{{ $key }}' ? 'bg-surface text-fg shadow-card ring-1 ring-line' : 'text-fg-muted hover:text-fg'">
                                    <x-ki :name="$icon" class="size-4" /> {{ $label }}
                                    <span x-show="showErrors && sectionErrors('{{ $key }}').length" class="size-2 rounded-full bg-danger" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>
                    </nav>

                    {{-- Error summary --}}
                    <div x-show="showErrors && errorCount" class="mt-5 flex items-start gap-3 rounded-panel border border-danger/25 bg-danger-soft px-4 py-3 text-danger-text" role="alert">
                        <x-ki name="alert" class="mt-0.5 size-5" />
                        <p class="font-medium" x-text="count(@js($countChoices('plans.fix_errors')), errorCount)"></p>
                    </div>

                    <form @submit.prevent="nextSection()" novalidate class="mt-5">
                        <div x-show="section === 'company'">@include('provider.partials.planner.company')</div>
                        <div x-show="section === 'contact'" x-cloak>@include('provider.partials.planner.contact')</div>
                        <div x-show="section === 'portfolio'" x-cloak>@include('provider.partials.planner.portfolio')</div>
                        <div x-show="section === 'media'" x-cloak>@include('provider.partials.planner.media')</div>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center">
                            <button type="button" class="btn btn-ghost" @click="prevSection()"><x-ki name="arrow-left" class="size-5" /> {{ __('plans.back') }}</button>
                            <button type="button" class="btn btn-ghost sm:ml-auto" @click="cancel()">{{ __('plans.cancel') }}</button>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <span x-show="nextSectionName" x-text="@js(__('plans.next')) + ': ' + sectionLabel(nextSectionName)"></span>
                                <span x-show="!nextSectionName">{{ __('plans.review_details') }}</span>
                                <x-ki name="arrow-right" class="size-5" />
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Live preview: beside the form on wide screens, toggled on phones --}}
                <aside class="min-w-0">
                    <button type="button" class="btn btn-secondary mb-4 w-full xl:hidden" @click="showPreview = !showPreview" :aria-expanded="showPreview.toString()">
                        <x-ki name="eye" class="size-5" />
                        <span x-show="!showPreview">{{ __('plans.preview_show') }}</span>
                        <span x-cloak x-show="showPreview">{{ __('plans.preview_hide') }}</span>
                    </button>
                    <div class="xl:sticky xl:top-28" :class="showPreview ? 'block' : 'hidden xl:block'">@include('provider.partials.listing-preview')</div>
                </aside>
            </div>
        </section>

        {{-- ============ Step 3: review ============ --}}
        <section x-cloak x-show="step === 'review'">
            <div class="grid grid-cols-1 gap-8 xl:grid-cols-[minmax(0,1fr)_21rem]">
                <div class="min-w-0 space-y-5">
                    {{-- Plan --}}
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-panel border border-line bg-surface p-5 shadow-card">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl" :class="{{ $planTile }}">
                                @foreach ($planIcon as $p => $icon)<x-ki :name="$icon" class="size-5" x-show="plan === '{{ $p }}'" />@endforeach
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm text-fg-subtle">{{ __('plans.listing_plan') }}</p>
                                <p class="text-lg font-semibold" x-text="planName()"></p>
                            </div>
                        </div>
                        <p class="flex flex-wrap items-center gap-1.5">
                            <x-status-badge tone="neutral" size="sm" :label="__('plans.state_selected')" />
                            <x-status-badge tone="pending" size="sm" :label="__('plans.state_not_active')" />
                            <x-status-badge tone="neutral" size="sm" :label="__('plans.state_payment')" />
                        </p>
                    </div>

                    {{-- Company --}}
                    <section class="card overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                            <h2 class="flex items-center gap-2 font-semibold"><x-ki name="building" class="size-5 text-fg-subtle" /> {{ __('plans.section_company') }}</h2>
                            <button type="button" class="btn btn-ghost btn-sm" @click="editSection('company')"><x-ki name="pencil" class="size-4" /> {{ __('plans.edit') }}</button>
                        </div>
                        <dl class="divide-y divide-line">
                            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]"><dt class="text-sm text-fg-subtle">{{ __('plans.fields.name') }}</dt><dd class="font-medium" style="overflow-wrap:anywhere" x-text="orDash(form.name)"></dd></div>
                            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]"><dt class="text-sm text-fg-subtle">{{ __('plans.fields.description') }}</dt>
                                <dd class="min-w-0"><p class="whitespace-pre-line text-fg-muted" style="overflow-wrap:anywhere" x-text="orDash(form.description)"></p><p class="mt-1 text-xs text-fg-subtle tabular-nums" lang="en" x-text="counterText"></p></dd></div>
                        </dl>
                    </section>

                    {{-- Contact and location --}}
                    <section class="card overflow-hidden" x-show="sections.includes('contact')">
                        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                            <h2 class="flex items-center gap-2 font-semibold"><x-ki name="map-pin" class="size-5 text-fg-subtle" /> {{ __('plans.section_contact') }}</h2>
                            <button type="button" class="btn btn-ghost btn-sm" @click="editSection('contact')"><x-ki name="pencil" class="size-4" /> {{ __('plans.edit') }}</button>
                        </div>
                        <dl class="divide-y divide-line">
                            <div x-show="has('email')" class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]"><dt class="text-sm text-fg-subtle">{{ __('plans.fields.email') }}</dt><dd class="font-medium" style="overflow-wrap:anywhere" x-text="orDash(form.email)"></dd></div>
                            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]"><dt class="text-sm text-fg-subtle">{{ __('plans.fields.address') }}</dt><dd class="font-medium" style="overflow-wrap:anywhere" x-text="orDash(form.address)"></dd></div>
                            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]"><dt class="text-sm text-fg-subtle">{{ __('plans.fields.website') }}</dt><dd class="font-medium" style="overflow-wrap:anywhere" x-text="orDash(form.website)"></dd></div>
                            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]"><dt class="text-sm text-fg-subtle">{{ __('plans.fields.phone') }}</dt><dd class="font-medium" lang="en" x-text="orDash(form.phone && formatPhone(form.phone))"></dd></div>
                            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[12rem_minmax(0,1fr)]"><dt class="text-sm text-fg-subtle">{{ __('plans.fields.whatsapp') }}</dt>
                                <dd class="font-medium" x-text="form.whatsappEnabled ? @js(__('plans.whatsapp_on', ['number' => '__N__'])).replace('__N__', formatPhone(form.whatsapp)) : @js(__('plans.whatsapp_off'))"></dd></div>
                        </dl>
                    </section>

                    {{-- Services and projects --}}
                    <section class="card overflow-hidden" x-show="sections.includes('portfolio')">
                        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                            <h2 class="flex items-center gap-2 font-semibold"><x-ki name="briefcase" class="size-5 text-fg-subtle" /> {{ __('plans.section_portfolio') }}</h2>
                            <button type="button" class="btn btn-ghost btn-sm" @click="editSection('portfolio')"><x-ki name="pencil" class="size-4" /> {{ __('plans.edit') }}</button>
                        </div>
                        <div class="grid gap-5 px-5 py-4 sm:grid-cols-2">
                            <div>
                                <p class="text-sm text-fg-subtle" x-text="count(@js($countChoices('plans.services_count')), form.services.length)"></p>
                                <ul class="mt-2 flex flex-wrap gap-1.5">
                                    <template x-for="service in form.services" :key="service.id"><li class="rounded-tag bg-success-soft px-2 py-1 text-xs font-semibold text-success-text" x-text="service.name"></li></template>
                                </ul>
                            </div>
                            <div>
                                <p class="text-sm text-fg-subtle" x-text="count(@js($countChoices('plans.projects_count')), form.projects.length)"></p>
                                <ul class="mt-2 space-y-1 text-sm">
                                    <template x-for="project in form.projects" :key="project.id"><li class="font-medium" style="overflow-wrap:anywhere"><span x-text="project.name"></span> <span class="font-normal text-fg-subtle" x-text="[project.place, project.year].filter(Boolean).join(', ')"></span></li></template>
                                </ul>
                            </div>
                        </div>
                    </section>

                    {{-- Photos and videos --}}
                    <section class="card overflow-hidden" x-show="sections.includes('media')">
                        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                            <h2 class="flex items-center gap-2 font-semibold"><x-ki name="photo" class="size-5 text-fg-subtle" /> {{ __('plans.section_media') }}</h2>
                            <button type="button" class="btn btn-ghost btn-sm" @click="editSection('media')"><x-ki name="pencil" class="size-4" /> {{ __('plans.edit') }}</button>
                        </div>
                        <div class="space-y-3 px-5 py-4">
                            <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                <div><dt class="text-fg-subtle">{{ __('plans.business_photos') }}</dt><dd class="font-semibold" x-text="count(@js($countChoices('plans.photos_count')), media.business.length)"></dd></div>
                                <div><dt class="text-fg-subtle">{{ __('plans.project_photos') }}</dt><dd class="font-semibold" x-text="count(@js($countChoices('plans.photos_count')), media.project.length)"></dd></div>
                                <div><dt class="text-fg-subtle">{{ __('plans.project_videos') }}</dt><dd class="font-semibold" x-text="count(@js($countChoices('plans.videos_count')), media.videos.length)"></dd></div>
                                <div><dt class="text-fg-subtle">{{ __('plans.video_links') }}</dt><dd class="font-semibold" x-text="count(@js($countChoices('plans.links_count')), form.videoLinks.length)"></dd></div>
                            </dl>
                            <div class="flex flex-wrap gap-1.5" x-show="media.business.length + media.project.length">
                                <template x-for="item in [...media.business, ...media.project].slice(0, 8)" :key="item.id"><img :src="item.url" alt="" class="size-14 rounded-md object-cover ring-1 ring-line"></template>
                            </div>
                            <p class="text-xs text-fg-subtle">{{ __('plans.saved_media_note') }}</p>
                        </div>
                    </section>

                    <x-alert tone="info">{{ __('plans.device_note') }}</x-alert>

                    <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center">
                        <button type="button" class="btn btn-ghost" @click="goSection(sections[sections.length - 1]); scrollTop()"><x-ki name="arrow-left" class="size-5" /> {{ __('plans.back') }}</button>
                        <button type="button" class="btn btn-ghost sm:ml-auto" @click="cancel()">{{ __('plans.cancel') }}</button>
                        <button type="button" class="btn btn-primary btn-lg" @click="save()"><x-ki name="check-circle" class="size-5" /> {{ __('plans.save_changes') }}</button>
                    </div>
                </div>

                <aside class="hidden min-w-0 xl:block">
                    <div class="sticky top-28">@include('provider.partials.listing-preview')</div>
                </aside>
            </div>
        </section>

        {{-- ============ Saved ============ --}}
        <section x-cloak x-show="step === 'saved'" class="max-w-3xl">
            <div class="card overflow-hidden">
                <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-start sm:p-8">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-success-soft text-forest ring-1 ring-forest/25 dark:text-forest-300"><x-ki name="check-circle" class="size-7" /></span>
                    <div class="min-w-0">
                        <p class="text-[0.95rem] text-fg-muted" x-text="@js(__('plans.saved_text', ['plan' => '__PLAN__'])).replace('__PLAN__', planName())"></p>
                        <p class="mt-3 flex flex-wrap items-center gap-1.5">
                            <x-status-badge tone="neutral" size="sm" :label="__('plans.state_selected')" />
                            <x-status-badge tone="pending" size="sm" :label="__('plans.state_not_active')" />
                        </p>
                        <p class="mt-3 text-sm text-fg-subtle" x-show="savedAtText">{{ __('plans.last_saved') }}: <span x-text="savedAtText"></span></p>
                        <p class="mt-2 text-sm text-fg-subtle" x-show="media.business.length + media.project.length + media.videos.length">{{ __('plans.saved_media_note') }}</p>
                    </div>
                </div>

                <div class="border-t border-line bg-sunken px-6 py-5 sm:px-8">
                    <h2 class="font-semibold">{{ __('plans.next_steps') }}</h2>
                    <ol class="mt-4 space-y-4">
                        @foreach ([
                            ['next_price', 'status_not_set', 'neutral'],
                            ['next_payment', 'status_not_connected', 'neutral'],
                            ['next_activation', 'state_not_active', 'pending'],
                        ] as $i => [$key, $status, $tone])
                            <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-3">
                                <span class="flex size-7 items-center justify-center rounded-full bg-surface text-xs font-bold text-fg-subtle ring-1 ring-line-strong">{{ $i + 1 }}</span>
                                <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                                    <div class="min-w-0">
                                        <p class="font-medium">{{ __('plans.'.$key) }}</p>
                                        <p class="text-sm text-fg-muted">{{ __('plans.'.$key.'_text') }}</p>
                                    </div>
                                    <x-status-badge :tone="$tone" size="sm" :label="__('plans.'.$status)" />
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="flex flex-col gap-3 border-t border-line px-6 py-4 sm:flex-row sm:justify-end sm:px-8">
                    <button type="button" class="btn btn-secondary" @click="startDetails()"><x-ki name="pencil" class="size-5" /> {{ __('plans.edit_details') }}</button>
                    <a href="{{ lroute('provider.dashboard') }}" class="btn btn-primary">{{ __('plans.back_to_overview') }} <x-ki name="arrow-right" class="size-5" /></a>
                </div>
            </div>
        </section>

        {{-- Discard confirmation --}}
        <dialog x-effect="if (confirmDiscard && !$el.open) $el.showModal(); if (!confirmDiscard && $el.open) $el.close()" @close="confirmDiscard = false"
                aria-labelledby="discard-title" class="m-auto w-[min(26rem,calc(100vw-2rem))] rounded-panel bg-surface p-0 text-fg shadow-raised backdrop:bg-ink/60">
            <div class="p-6">
                <h2 id="discard-title" class="type-heading text-lg">{{ __('plans.discard_title') }}</h2>
                <p class="mt-2 text-fg-muted">{{ __('plans.discard_text') }}</p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" x-ref="keepEditing" class="btn btn-secondary" @click="confirmDiscard = false">{{ __('plans.keep_editing') }}</button>
                    <button type="button" class="btn btn-danger" @click="discard()">{{ __('plans.discard') }}</button>
                </div>
            </div>
        </dialog>
    </div>
</x-layouts.dashboard>
