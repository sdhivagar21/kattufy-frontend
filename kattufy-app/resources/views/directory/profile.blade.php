@use('App\Modules\Accounts\Enums\Role')
{{--
    Provider profile. No phone, WhatsApp or email in the HTML: numbers are
    fetched only after a tap, for a logged-in customer (Architecture §6C).
--}}
@php
    $tamil = app()->getLocale() === 'ta';
    $showTamil = $tamil && $provider->description_ta_status === 'reviewed' && filled($provider->description_ta);
    $description = $showTamil ? $provider->description_ta : $provider->description_en;
    $canReveal = auth()->user()->hasRole(Role::Customer->value);
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $credentials = $provider->publicCredentials();
    // Listing plan (App\Support\ListingPlans). No stored plan yet = existing layout.
    $plan = \App\Support\ListingPlans::of($provider);
    $show = fn (string $field) => \App\Support\ListingPlans::shows($plan, $field);
    $website = $provider->website_url && preg_match('#^https?://#i', $provider->website_url) && filter_var($provider->website_url, FILTER_VALIDATE_URL) ? $provider->website_url : null;
@endphp
<x-layouts.site :title="$provider->name().' — '.tr_field($category, 'name')">
    <section class="border-b border-line bg-surface">
        <div class="container-page py-8 sm:py-10">
            @include('directory.partials.breadcrumbs', ['crumbs' => [
                __('ui.home') => \App\Modules\Accounts\Services\AccountHome::url(auth()->user()),
                tr_field($category, 'name') => lroute('directory.section', $category->slug),
                tr_field($provider->city ?? \App\Modules\Geography\Models\City::launch(), 'name') => lroute('directory.child', [$category->slug, ($provider->city ?? \App\Modules\Geography\Models\City::launch())->slug]),
                $provider->name() => null,
            ]])
            <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-start">
                <span class="flex size-20 shrink-0 items-center justify-center rounded-panel bg-ink text-3xl font-bold text-amber shadow-raised dark:bg-white/10" style="font-stretch:116%" lang="en">{{ $provider->initials() }}</span>
                <div class="min-w-0">
                    <h1 class="type-display text-[1.9rem] sm:text-[2.4rem] ta:text-[1.6rem] sm:ta:text-[2rem]">{{ $provider->name() }}</h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-fg-muted">
                        @if ($provider->subtype)<span>{{ tr_field($provider->subtype, 'name') }}</span>@endif
                        @if ($provider->locality)<span class="inline-flex items-center gap-1.5"><x-ki name="map-pin" class="size-4" />{{ tr_field($provider->locality, 'name') }}, {{ tr_field($provider->locality->city ?? $provider->city, 'name') }}</span>@endif
                        @if ($provider->established_year)<span>{{ __('directory.since', ['year' => $provider->established_year]) }}</span>@endif
                    </p>
                    <ul class="mt-4 flex flex-wrap gap-1.5" aria-label="{{ __('directory.checks') }}">
                        @if ($provider->isPhoneVerified())
                            <li class="inline-flex items-center gap-1.5 rounded-tag bg-success-soft px-2.5 py-1 text-[0.8125rem] font-semibold text-success-text"><x-ki name="phone" class="size-4" />{{ __('directory.badge_phone') }}</li>
                        @endif
                        @foreach ($credentials as $credential)
                            <li class="inline-flex items-center gap-1.5 rounded-tag bg-success-soft px-2.5 py-1 text-[0.8125rem] font-semibold text-success-text"><x-ki name="shield" class="size-4" />{{ __('directory.checked_with', ['authority' => tr_field($credential->authority, 'name')]) }}</li>
                        @endforeach
                        @if ($provider->hasPortfolioShown())
                            <li class="inline-flex items-center gap-1.5 rounded-tag bg-surface-2 px-2.5 py-1 text-[0.8125rem] font-semibold text-fg-muted"><x-ki name="sparkle" class="size-4" />{{ __('directory.badge_portfolio') }}</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="container-page grid grid-cols-1 gap-10 py-10 pb-28 lg:grid-cols-[minmax(0,1fr)_21rem] lg:pb-10">
        <div class="min-w-0 space-y-12">
            @if ($show('description'))
            <section aria-labelledby="about-title">
                <h2 id="about-title" class="type-heading text-[1.4rem]">{{ __('directory.about') }}</h2>
                <div class="prose-kattufy mt-4" lang="{{ $showTamil ? 'ta' : 'en' }}">
                    @if ($tamil && ! $showTamil)
                        <p class="text-sm text-fg-subtle" lang="ta">{{ __('directory.tamil_not_available') }}</p>
                    @endif
                    @foreach (preg_split('/\n{2,}/', (string) $description) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </section>
            @endif

            @if ($show('projects') && $provider->portfolioItems->where('is_published', true)->isNotEmpty())
                <section aria-labelledby="portfolio-title">
                    <div class="flex items-baseline justify-between gap-4">
                        <h2 id="portfolio-title" class="type-heading text-[1.4rem]">{{ __('directory.past_work') }}</h2>
                        <span class="text-sm text-fg-subtle">{{ trans_choice('provider.projects_count', $provider->portfolioItems->count(), ['count' => $provider->portfolioItems->count()]) }}</span>
                    </div>
                    <ul class="mt-5 grid gap-5 sm:grid-cols-2">
                        @foreach ($provider->portfolioItems->where('is_published', true) as $item)
                            <li @class(['sm:col-span-2' => $loop->first && $loop->count % 2 === 1])>
                                <figure>
                                    <div class="aspect-[16/10] overflow-hidden rounded-panel bg-surface-2 ring-1 ring-line">
                                        @if ($item->image)
                                            <img src="{{ $item->image->publicUrl(800) }}" srcset="{{ $item->image->publicUrl(400) }} 400w, {{ $item->image->publicUrl(800) }} 800w" sizes="(min-width: 1024px) 720px, 100vw"
                                                 alt="{{ $tamil && $item->image->alt_ta ? $item->image->alt_ta : $item->image->alt_en }}" loading="lazy" width="800" height="500" class="size-full object-cover">
                                        @endif
                                    </div>
                                    <figcaption class="mt-2.5">
                                        <span class="block font-medium">{{ tr_field($item, 'title') }}</span>
                                        <span class="block text-sm text-fg-subtle">{{ collect([$item->locality ? tr_field($item->locality, 'name') : null, $item->completed_on?->year])->filter()->join(', ') }}</span>
                                    </figcaption>
                                </figure>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="what-title">
                <h2 id="what-title" class="type-heading text-[1.4rem]">{{ __('directory.what_they_do') }}</h2>
                <dl class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-semibold text-fg-subtle">{{ __('directory.listed_under') }}</dt>
                        <dd class="mt-2 flex flex-wrap gap-2">@foreach ($provider->categories as $c)<a href="{{ lroute('directory.section', $c->slug) }}" class="link">{{ tr_field($c, 'name') }}</a>@endforeach</dd>
                    </div>
                    @if ($show('services') && $provider->services->isNotEmpty())
                        <div>
                            <dt class="text-sm font-semibold text-fg-subtle">{{ __('directory.specialisations') }}</dt>
                            <dd class="mt-2"><ul class="space-y-1.5">@foreach ($provider->services as $service)<li class="flex gap-2"><x-ki name="check" class="mt-1 size-4 text-forest dark:text-forest-300" />{{ tr_field($service, 'name') }}</li>@endforeach</ul></dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if ($provider->zones->isNotEmpty())
                <section aria-labelledby="areas-title">
                    <h2 id="areas-title" class="type-heading text-[1.4rem]">{{ __('directory.service_areas') }}</h2>
                    <p class="mt-1 text-fg-muted">{{ __('directory.service_areas_text') }}</p>
                    <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($provider->zones as $zone)
                            <li class="card p-4">
                                <p class="font-semibold" @if ($tamil && ! $zone->name_ta) lang="en" @endif>{{ tr_field($zone, 'name') }}</p>
                                <p class="mt-1 text-sm text-fg-muted">{{ $zone->localities->map(fn ($l) => tr_field($l, 'name'))->join(', ') }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($credentials->isNotEmpty())
                <section aria-labelledby="credentials-title">
                    <h2 id="credentials-title" class="type-heading text-[1.4rem]">{{ __('directory.credentials_title') }}</h2>
                    <p class="mt-1 max-w-prose text-fg-muted">{{ __('directory.credentials_text') }}</p>
                    <div class="mt-4 overflow-hidden rounded-panel border border-line bg-surface">
                        <table class="w-full text-left text-[0.95rem]">
                            <thead class="bg-surface-2 text-sm text-fg-subtle">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('directory.credential') }}</th>
                                    <th scope="col" class="hidden px-4 py-3 font-semibold sm:table-cell">{{ __('directory.issued_by') }}</th>
                                    <th scope="col" class="px-4 py-3 font-semibold">{{ __('directory.last_checked') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($credentials as $credential)
                                    <tr>
                                        <td class="px-4 py-3.5 align-top font-medium">{{ tr_field($credential->type, 'name') }}<span class="block text-sm font-normal text-fg-subtle sm:hidden">{{ tr_field($credential->authority, 'name') }}</span></td>
                                        <td class="hidden px-4 py-3.5 align-top text-fg-muted sm:table-cell">{{ tr_field($credential->authority, 'name') }}</td>
                                        <td class="px-4 py-3.5 align-top whitespace-nowrap text-fg-muted">{{ ist($credential->verified_at, 'M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        {{-- Contact and details --}}
        <aside class="space-y-6">
            <section id="contact" class="card card-pad lg:sticky lg:top-28" aria-labelledby="contact-title"
                     x-data="contactReveal({ url: @js(route('contact.reveal', $provider)), locale: @js(app()->getLocale()) })">
                <h2 id="contact-title" class="type-heading text-lg">{{ __('directory.contact', ['name' => $provider->name()]) }}</h2>
                @if ($canReveal)
                    <p class="mt-1.5 text-sm text-fg-muted">{{ __('directory.contact_text') }}</p>
                    <div class="mt-4 grid gap-2.5">
                        @if ($show('phone') && $provider->allow_call)
                            <button type="button" @click="reveal('call')" class="btn btn-primary w-full" :disabled="loading !== null">
                                <x-ki name="phone" class="size-5" /> <span x-text="loading === 'call' ? @js(__('ui.please_wait')) : @js(__('directory.call'))">{{ __('directory.call') }}</span>
                            </button>
                        @endif
                        @if ($show('whatsapp') && $provider->allow_whatsapp)
                            <button type="button" @click="reveal('whatsapp')" class="btn btn-secondary w-full" :disabled="loading !== null">
                                <x-ki name="chat" class="size-5" /> <span x-text="loading === 'whatsapp' ? @js(__('ui.please_wait')) : 'WhatsApp'">WhatsApp</span>
                            </button>
                        @endif
                        @if ($provider->allow_enquiry)
                            <a href="{{ lroute('enquiry') }}?category={{ $category->slug }}&source=direct" class="btn btn-ghost w-full">{{ __('ui.send_enquiry') }}</a>
                        @endif
                    </div>
                    <div x-cloak x-show="result" x-transition class="mt-4 rounded-control bg-success-soft p-4 text-center">
                        <p class="text-sm text-success-text">{{ __('directory.number') }}</p>
                        <p class="mt-1 font-mono text-xl font-bold tracking-wide text-fg" x-text="result?.display"></p>
                        <div class="mt-3 flex justify-center gap-2">
                            <a :href="result?.tel" x-show="result?.tel" class="btn btn-primary btn-sm"><x-ki name="phone" class="size-4" /> {{ __('directory.call_now') }}</a>
                            <a :href="result?.whatsapp" x-show="result?.whatsapp" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">WhatsApp</a>
                        </div>
                    </div>
                    <p x-cloak x-show="error" x-text="error" class="field-error mt-3" role="alert"></p>
                    <p class="mt-4 text-xs text-fg-subtle">{{ __('directory.reveal_note', ['count' => config('kattufy.reveal_limit_per_day')]) }}</p>
                @else
                    <p class="mt-2 text-sm text-fg-muted">{{ __('directory.reveal_customers_only') }}</p>
                @endif

                {{-- Plan-specific public details (address on Pro and Premium, email on Premium; only once a plan is stored) --}}
                @if ($website && $show('website'))
                    <a href="{{ $website }}" target="_blank" rel="noopener nofollow" class="btn btn-ghost mt-2 w-full"><x-ki name="external" class="size-5" /> {{ __('plans.visit_website') }}</a>
                @endif
                @if ($plan && $show('address') && $provider->address)
                    <p class="mt-4 flex gap-2 border-t border-line pt-4 text-sm"><x-ki name="map-pin" class="mt-0.5 size-4 text-fg-subtle" /> {{ $provider->address }}</p>
                @endif
                @if ($plan && $show('email') && $provider->email)
                    <a href="mailto:{{ $provider->email }}" class="mt-4 flex gap-2 border-t border-line pt-4 text-sm hover:underline" style="overflow-wrap:anywhere"><x-ki name="mail" class="mt-0.5 size-4 text-fg-subtle" /> {{ $provider->email }}</a>
                @endif
            </section>

            <section class="card card-pad">
                <h2 class="font-semibold">{{ __('directory.details') }}</h2>
                <dl class="mt-3 space-y-2 text-[0.95rem]">
                    @if ($provider->published_at)
                        <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('directory.listed_since') }}</dt><dd>{{ ist($provider->published_at, 'M Y') }}</dd></div>
                    @endif
                    @if ($provider->last_verified_at)
                        <div class="flex justify-between gap-4"><dt class="text-fg-subtle">{{ __('directory.last_updated') }}</dt><dd>{{ ist($provider->last_verified_at, 'M Y') }}</dd></div>
                    @endif
                </dl>
                @if ($provider->working_hours)
                    <h3 class="mt-5 text-sm font-semibold">{{ __('directory.working_hours') }}</h3>
                    <dl class="mt-1 divide-y divide-line text-[0.95rem]">
                        @foreach ($days as $day)
                            @if (isset($provider->working_hours[$day]))
                                <div @class(['flex justify-between gap-4 py-2', 'font-semibold' => strtolower(now('Asia/Kolkata')->englishDayOfWeek) === $day])>
                                    <dt>{{ __('ui.day_'.$day) }}</dt>
                                    <dd @class(['text-fg-subtle' => $provider->working_hours[$day] === 'Closed']) lang="en">{{ $provider->working_hours[$day] === 'Closed' ? __('directory.closed') : $provider->working_hours[$day] }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                @endif
            </section>

            @if ($more->isNotEmpty())
                <section>
                    <h2 class="font-semibold">{{ __('directory.more_in', ['category' => tr_field($category, 'name')]) }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($more as $other)
                            <li><a href="{{ lroute('directory.child', [$category->slug, $other->slug]) }}" class="card flex items-center gap-3 p-3 hover:border-line-strong">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-ink text-sm font-bold text-amber dark:bg-white/10" lang="en">{{ $other->initials() }}</span>
                                <span class="min-w-0"><span class="block truncate font-medium">{{ $other->name() }}</span><span class="block text-sm text-fg-subtle">{{ $other->locality ? tr_field($other->locality, 'name') : '' }}</span></span>
                            </a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>

    {{-- Phone: contact bar --}}
    @if ($canReveal && (($show('phone') && $provider->allow_call) || ($show('whatsapp') && $provider->allow_whatsapp)))
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgb(18_25_30/0.25)] backdrop-blur lg:hidden">
            <a href="#contact" class="btn btn-primary w-full"><x-ki name="phone" class="size-5" /> {{ __('directory.contact_short') }}</a>
        </div>
    @endif
</x-layouts.site>
