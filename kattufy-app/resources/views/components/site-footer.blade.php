@php
    $member = auth()->check() && auth()->user()->isVerified();
    $isCustomer = $member && auth()->user()->hasRole('customer');
@endphp
<footer class="mt-auto bg-ink text-white dark:bg-ink-950">
    <div class="bg-blueprint">
        <div class="container-page grid gap-10 py-12 md:grid-cols-[1.4fr_repeat(3,1fr)]">
            <div class="max-w-sm">
                <x-brand tone="dark" />
                <p class="mt-4 text-[0.95rem] leading-relaxed text-white/70">{{ __('ui.footer_about') }}</p>
                <div class="mt-5"><x-lang-switch full /></div>
            </div>

            @if ($member)
                @if ($isCustomer)
                <div>
                    <h2 class="text-sm font-semibold text-amber">{{ __('ui.find_services') }}</h2>
                    <ul class="mt-3 space-y-2 text-[0.95rem] text-white/75">
                        <li><a class="hover:text-white" href="{{ lroute('directory.section', 'building-a-house') }}">{{ __('ui.group_building') }}</a></li>
                        <li><a class="hover:text-white" href="{{ lroute('directory.section', 'interiors-and-finishing') }}">{{ __('ui.group_interiors') }}</a></li>
                        <li><a class="hover:text-white" href="{{ lroute('directory.section', 'materials-and-supplies') }}">{{ __('ui.group_materials') }}</a></li>
                        <li><a class="hover:text-white" href="{{ lroute('search') }}">{{ __('ui.search') }}</a></li>
                    </ul>
                </div>
                @else
                <div></div>
                @endif
                <div>
                    <h2 class="text-sm font-semibold text-amber">{{ __('ui.help') }}</h2>
                    <ul class="mt-3 space-y-2 text-[0.95rem] text-white/75">
                        <li><a class="hover:text-white" href="{{ lroute('ranking') }}">{{ __('ui.how_listings_are_ordered') }}</a></li>
                        <li><a class="hover:text-white" href="{{ lroute('faq') }}">{{ __('ui.faq') }}</a></li>
                        <li><a class="hover:text-white" href="{{ lroute('guides') }}">{{ __('ui.guides') }}</a></li>
                        <li><a class="hover:text-white" href="{{ lroute('enquiry') }}">{{ __('ui.send_enquiry') }}</a></li>
                    </ul>
                </div>
            @else
                <div>
                    <h2 class="text-sm font-semibold text-amber">{{ __('ui.account') }}</h2>
                    <ul class="mt-3 space-y-2 text-[0.95rem] text-white/75">
                        <li><a class="hover:text-white" href="{{ lroute('login') }}">{{ __('ui.login') }}</a></li>
                        <li><a class="hover:text-white" href="{{ lroute('register') }}">{{ __('ui.register') }}</a></li>
                    </ul>
                </div>
                <div></div>
            @endif

            <div>
                <h2 class="text-sm font-semibold text-amber">{{ __('ui.legal') }}</h2>
                <ul class="mt-3 space-y-2 text-[0.95rem] text-white/75">
                    <li><a class="hover:text-white" href="{{ lroute('privacy') }}">{{ __('ui.privacy') }}</a></li>
                    <li><a class="hover:text-white" href="{{ lroute('terms') }}">{{ __('ui.terms') }}</a></li>
                    <li><a class="hover:text-white" href="{{ lroute('disclaimer') }}">{{ __('ui.disclaimer') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="container-page flex flex-col gap-1 py-5 text-sm text-white/55 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ date('Y') }} Kattufy. {{ __('ui.location') }}</p>
                <p lang="en">{{ config('kattufy.brand.slogan') }}</p>
            </div>
        </div>
    </div>
</footer>
