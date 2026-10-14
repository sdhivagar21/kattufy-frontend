<x-layouts.dashboard :title="__('user.dashboard_title')" area="user">
    {{-- Welcome --}}
    <section class="relative overflow-hidden rounded-panel bg-ink text-white shadow-raised dark:bg-ink-900">
        <div class="bg-blueprint absolute inset-0" aria-hidden="true"></div>
        <div class="absolute -top-20 -right-16 size-72 rounded-full bg-amber/15 blur-3xl" aria-hidden="true"></div>
        <div class="relative grid grid-cols-1 gap-8 p-6 sm:p-8 lg:grid-cols-[1.2fr_1fr] lg:items-end lg:p-10">
            <div>
                <p class="inline-flex items-center gap-2 text-sm font-medium text-amber"><x-ki name="map-pin" class="size-4" /> {{ __('ui.location') }}</p>
                <h1 class="type-display mt-3 text-[1.85rem] text-white sm:text-[2.4rem] ta:text-[1.5rem] sm:ta:text-[1.85rem]">{{ __('user.welcome', ['name' => $user->name]) }}</h1>
                <p class="mt-3 max-w-xl text-white/75">{{ __('user.welcome_intro') }}</p>
            </div>

            <form method="GET" action="{{ lroute('search') }}" class="rounded-panel bg-white/[0.06] p-4 ring-1 ring-white/10 backdrop-blur sm:p-5" role="search">
                <label for="q" class="text-sm font-semibold text-white">{{ __('directory.what_do_you_need') }}</label>
                <input id="q" name="q" type="search" list="dashboard-services" placeholder="{{ __('directory.search_placeholder') }}"
                       class="mt-1.5 block min-h-12 w-full rounded-control border-0 bg-white px-3.5 text-base text-ink placeholder:text-ink-600/60 focus:ring-4 focus:ring-amber/40 focus:outline-none">
                <datalist id="dashboard-services">
                    @foreach ($groups as $group) @foreach ($group->categories as $category)<option value="{{ tr_field($category, 'name') }}"></option>@endforeach @endforeach
                </datalist>
                <label for="area" class="mt-3 block text-sm font-semibold text-white">{{ __('directory.area_or_pin') }}</label>
                <div class="mt-1.5 flex gap-2">
                    <input id="area" name="area" type="text" list="dashboard-areas" placeholder="{{ __('directory.area_placeholder') }}"
                           class="block min-h-12 w-full min-w-0 rounded-control border-0 bg-white px-3.5 text-base text-ink placeholder:text-ink-600/60 focus:ring-4 focus:ring-amber/40 focus:outline-none">
                    <datalist id="dashboard-areas">
                        @foreach ($localities as $locality)<option value="{{ tr_field($locality, 'name') }}"></option>@endforeach
                    </datalist>
                    <button type="submit" class="btn btn-primary shrink-0"><x-ki name="search" /><span class="sr-only sm:not-sr-only">{{ __('ui.search') }}</span></button>
                </div>
            </form>
        </div>
    </section>

    <div class="mt-8">
        <div class="min-w-0 space-y-10">
            {{-- Services --}}
            <section aria-labelledby="services-title">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="services-title" class="type-heading text-[1.35rem]">{{ __('user.services_title') }}</h2>
                        <p class="mt-1 text-fg-muted">{{ __('user.services_intro') }}</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    @foreach ($groups as $group)
                        <div class="card flex flex-col p-5">
                            <a href="{{ lroute('directory.section', $group->slug) }}" class="type-heading text-[1.05rem] hover:text-accent-text">{{ tr_field($group, 'name') }}</a>
                            <ul class="mt-3 space-y-1">
                                @foreach ($group->categories as $category)
                                    @php
                                        $pivot = $category->cities->first()?->pivot;
                                        $live = $pivot?->status === 'live';
                                    @endphp
                                    <li>
                                        <a href="{{ lroute('directory.section', $category->slug) }}" class="flex min-h-10 items-center justify-between gap-2 rounded-md px-2 text-[0.95rem] hover:bg-surface-2">
                                            <span @class(['font-medium text-fg' => $live, 'text-fg-muted' => ! $live])>{{ tr_field($category, 'name') }}</span>
                                            @if ($live)
                                                <span class="shrink-0 text-xs font-semibold text-success-text">{{ trans_choice('directory.provider_count', $pivot->published_provider_count, ['count' => $pivot->published_provider_count]) }}</span>
                                            @else
                                                <span class="shrink-0 rounded-tag bg-surface-2 px-1.5 py-0.5 text-xs text-fg-subtle">{{ __('directory.coming_soon') }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Recently verified providers --}}
            @if ($featured->isNotEmpty())
                <section aria-labelledby="featured-title">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 id="featured-title" class="type-heading text-[1.35rem]">{{ __('user.featured_title') }}</h2>
                            <p class="mt-1 text-fg-muted">{{ __('user.featured_intro') }}</p>
                        </div>
                        <a href="{{ lroute('search') }}" class="link text-sm">{{ __('user.see_all') }}</a>
                    </div>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 2xl:grid-cols-3">
                        @foreach ($featured as $provider)
                            <x-provider-card :provider="$provider" />
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- How verification works --}}
            {{-- The user's own enquiries (Kattufy has enquiries, not bookings) --}}
            <section aria-labelledby="enquiries-title">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="enquiries-title" class="type-heading text-[1.35rem]">{{ __('user.enquiries_title') }}</h2>
                        <p class="mt-1 text-fg-muted">{{ __('user.enquiries_intro') }}</p>
                    </div>
                    <a href="{{ lroute('enquiry') }}" class="btn btn-secondary btn-sm"><x-ki name="chat" class="size-4" /> {{ __('ui.send_enquiry') }}</a>
                </div>
                @if ($enquiries->isEmpty())
                    <x-empty-state class="mt-5 py-8" icon="inbox" :title="__('user.no_enquiries')" :text="__('user.no_enquiries_text')" />
                @else
                    <ul class="mt-5 divide-y divide-line overflow-hidden rounded-panel border border-line bg-surface shadow-card">
                        @foreach ($enquiries as $enquiry)
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">{{ $enquiry->category ? tr_field($enquiry->category, 'name') : __('ui.send_enquiry') }}
                                        @if ($enquiry->locality)<span class="font-normal text-fg-subtle">· {{ tr_field($enquiry->locality, 'name') }}</span>@endif</p>
                                    <p class="mt-0.5 line-clamp-1 text-sm text-fg-muted">{{ $enquiry->message }}</p>
                                    <p class="mt-1 text-xs text-fg-subtle"><span class="font-mono">{{ $enquiry->reference_no }}</span> · {{ ist($enquiry->created_at, 'j M Y') }}</p>
                                </div>
                                <x-status-badge size="sm"
                                    :tone="match ($enquiry->status) { 'new' => 'pending', 'closed' => 'neutral', default => 'success' }"
                                    :label="__('user.enquiry_status_'.$enquiry->status)" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section aria-labelledby="trust-title" class="card card-pad">
                <h2 id="trust-title" class="type-heading text-[1.2rem]">{{ __('user.trust_title') }}</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-3">
                    @foreach (['phone' => 'phone', 'shield' => 'documents', 'check-circle' => 'status'] as $icon => $key)
                        <div>
                            <span class="flex size-10 items-center justify-center rounded-xl bg-success-soft text-success-text"><x-ki :name="$icon" /></span>
                            <h3 class="mt-3 font-semibold">{{ __('user.trust_'.$key.'_title') }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-fg-muted">{{ __('user.trust_'.$key.'_text') }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-5 border-t border-line pt-4 text-sm text-fg-subtle">{{ __('user.trust_demo_note') }}</p>
            </section>

            {{-- FAQ --}}
            <section aria-labelledby="faq-title">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 id="faq-title" class="type-heading text-[1.35rem]">{{ __('ui.faq') }}</h2>
                    <a href="{{ lroute('user.faqs') }}" class="link text-sm">{{ __('user.all_questions') }}</a>
                </div>
                <x-faq-list :items="$faqs" class="mt-5" />
            </section>
        </div>

        {{-- Account details live on the dedicated profile page. --}}
        <aside class="space-y-5">
            <section class="card card-pad">
                <h2 class="font-semibold">{{ __('user.cant_find_title') }}</h2>
                <p class="mt-1.5 text-sm text-fg-muted">{{ __('user.cant_find_text') }}</p>
                <a href="{{ lroute('enquiry') }}" class="btn btn-secondary btn-sm mt-4 w-full"><x-ki name="chat" class="size-4" /> {{ __('ui.send_enquiry') }}</a>
            </section>
        </aside>
    </div>
</x-layouts.dashboard>
