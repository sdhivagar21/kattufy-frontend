@props(['title' => null, 'area' => 'user', 'heading' => null, 'subheading' => null])
{{--
    User and Provider areas. Each area has its own navigation (never mixed);
    a sidebar on large screens, a scrollable tab bar on phones.
--}}
@php
    $user = auth()->user();
    $provider = $area === 'provider' ? $user->provider : null;
    $approved = $provider?->verificationState()->opensDashboard() ?? false;

    $items = match ($area) {
        // Approved providers get the full dashboard; Premium-only sections (4th
        // value true) appear when Premium is the selected listing plan.
        'provider' => $approved ? [
            ['provider.dashboard', 'grid', __('ui.nav_overview')],
            ['provider.profile', 'building', __('ui.nav_company_profile')],
            ['provider.plan', 'sparkle', __('ui.nav_listing_plan')],
            ['provider.portfolio', 'briefcase', __('ui.nav_services_projects'), true],
            ['provider.media', 'grid', __('ui.nav_media'), true],
            ['provider.verification', 'shield', __('ui.nav_verification')],
            ['provider.account', 'user', __('ui.nav_account')],
        ] : [
            ['provider.verification', 'shield', __('ui.nav_verification')],
            ['provider.register', 'pencil', __('ui.nav_business_details')],
            ['faq', 'question', __('ui.faq')],
        ],
        default => [
            ['user.dashboard', 'grid', __('ui.nav_dashboard')],
            ['user.profile', 'user', __('user.my_profile')],
            ['search', 'search', __('ui.find_services')],
            ['enquiry', 'chat', __('ui.send_enquiry')],
            ['user.faqs', 'question', __('ui.faq')],
        ],
    };

    $current = str_replace('ta.', '', (string) request()->route()?->getName());
    $planKey = $provider ? \App\Support\ListingPlans::storageKey($provider) : null;
@endphp
<x-layouts.base :title="$title">
    <x-demo-banner />
    <x-site-header :area="$area" />

    {{-- Phone: tab bar --}}
    <nav class="sticky top-[calc(4.25rem+3px)] sm:top-[calc(4.75rem+3px)] z-20 border-b border-line bg-surface/95 backdrop-blur lg:hidden" aria-label="{{ __('ui.area_navigation') }}">
        <div class="flex gap-1 overflow-x-auto px-3 py-2 [scrollbar-width:none]">
            @foreach ($items as $item)
                @php [$route, $icon, $label] = $item; $premiumOnly = $item[3] ?? false; @endphp
                <a href="{{ lroute($route) }}" @if ($current === $route) aria-current="page" @endif
                   @if ($premiumOnly) x-data="planState({ key: @js($planKey) })" x-show="plan === 'premium' || {{ $current === $route ? 'true' : 'false' }}" x-cloak @endif
                   @class([
                       'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full px-3.5 text-sm font-medium transition-colors',
                       'bg-ink text-white dark:bg-amber dark:text-ink' => $current === $route,
                       'text-fg-muted hover:bg-surface-2 hover:text-fg' => $current !== $route,
                   ])>
                    <x-ki :name="$icon" class="size-4" /> {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>

    <div class="container-page flex-1 py-6 sm:py-8 lg:grid grid-cols-1 lg:grid-cols-[15.5rem_minmax(0,1fr)] lg:gap-10 lg:py-10">
        {{-- Large screens: sidebar --}}
        <aside class="hidden lg:block">
            <div class="sticky top-28 space-y-6">
                <div class="card card-pad">
                    <p class="eyebrow">{{ $area === 'provider' ? __('ui.area_provider') : __('ui.area_user') }}</p>
                    <p class="mt-2 truncate font-semibold">{{ $area === 'provider' && $provider ? $provider->business_name_en : $user->name }}</p>
                    <p class="mt-0.5 text-sm text-fg-subtle">{{ \App\Support\Phone::mask($user->phone) }}</p>
                </div>
                <nav class="space-y-1" aria-label="{{ __('ui.area_navigation') }}">
                    @foreach ($items as $item)
                @php [$route, $icon, $label] = $item; $premiumOnly = $item[3] ?? false; @endphp
                        <a href="{{ lroute($route) }}" @if ($current === $route) aria-current="page" @endif
                   @if ($premiumOnly) x-data="planState({ key: @js($planKey) })" x-show="plan === 'premium' || {{ $current === $route ? 'true' : 'false' }}" x-cloak @endif
                           @class([
                               'group relative flex min-h-11 items-center gap-3 rounded-control px-3 text-[0.95rem] font-medium transition-colors',
                               'bg-surface text-fg shadow-card ring-1 ring-line' => $current === $route,
                               'text-fg-muted hover:bg-surface hover:text-fg' => $current !== $route,
                           ])>
                            @if ($current === $route)
                                <span class="absolute inset-y-2 left-0 w-1 rounded-full bg-amber" aria-hidden="true"></span>
                            @endif
                            <x-ki :name="$icon" @class(['size-[1.15rem]', 'text-amber-700 dark:text-amber' => $current === $route, 'text-fg-subtle group-hover:text-fg' => $current !== $route]) />
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
                    <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-control px-3 text-[0.95rem] font-medium text-fg-muted transition-colors hover:bg-danger-soft hover:text-danger-text">
                        <x-ki name="logout" class="size-[1.15rem]" /> {{ __('ui.logout') }}
                    </button>
                </form>
            </div>
        </aside>

        <main id="main" tabindex="-1" class="min-w-0">
            <x-flash />
            @if ($heading)
                <header class="mb-6 sm:mb-8">
                    <h1 class="type-display text-[1.75rem] sm:text-[2.1rem] ta:text-[1.45rem] sm:ta:text-[1.7rem]">{{ $heading }}</h1>
                    @if ($subheading)
                        <p class="mt-2 max-w-2xl text-fg-muted">{{ $subheading }}</p>
                    @endif
                </header>
            @endif
            {{ $slot }}
        </main>
    </div>

    <x-site-footer />
</x-layouts.base>
