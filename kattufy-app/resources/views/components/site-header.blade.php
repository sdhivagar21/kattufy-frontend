@props(['area' => null])
{{--
    Main header: logo + exact slogan, navigation, language, theme and account.
    Charcoal in both themes (brand navigation colour). On small screens the
    navigation and account links move into a menu panel.
--}}
@use('App\Modules\Accounts\Enums\Role')
@use('App\Support\Phone')
@php
    $user = auth()->user();
    $role = $user?->chosenRole();
    // Directory browsing is the customer area; providers and staff get their own navigation.
    $isCustomer = $role === Role::Customer;
    $groups = $isCustomer ? \App\Modules\Directory\Models\CategoryGroup::with('categories')->where('is_active', true)->orderBy('sort_order')->get() : collect();
    $homeUrl = $user ? \App\Modules\Accounts\Services\AccountHome::url($user) : lroute('home');
    $areaLabel = match (true) {
        $area === 'provider' => __('ui.area_provider'),
        $area === 'user' => __('ui.area_user'),
        default => null,
    };
    $navLink = 'inline-flex min-h-11 items-center gap-1.5 rounded-control px-3 text-[0.95rem] font-medium text-white/85 transition-colors hover:bg-white/8 hover:text-white';
@endphp
<header x-data="{ menu: false }" @keydown.escape.window="menu = false"
        class="sticky top-0 z-30 border-b border-white/10 bg-ink text-white shadow-[0_1px_0_rgb(0_0_0/0.25)] dark:bg-ink-950">
    {{-- Thin amber rule: the "measuring tape" accent. --}}
    <div class="h-[3px] bg-gradient-to-r from-amber via-amber-300 to-amber" aria-hidden="true"></div>

    <div class="container-page flex min-h-[4.25rem] items-center gap-3 py-2 sm:min-h-[4.75rem]">
        <x-brand :href="$homeUrl" tone="dark" />

        @auth
            @if ($isCustomer)
                <div class="relative z-50 hidden shrink-0 sm:block" x-data="{ accountOpen: false, moveMenu(event) { const items = Array.from(this.$refs.accountMenu.querySelectorAll('[role=menuitem]')); if (!items.length) return; let index = items.indexOf(event.target); if (event.key === 'Home') index = 0; else if (event.key === 'End') index = items.length - 1; else if (event.key === 'ArrowDown') index = (index + 1) % items.length; else if (event.key === 'ArrowUp') index = (index <= 0 ? items.length : index) - 1; else return; event.preventDefault(); items[index].focus(); } }" @click.outside="accountOpen = false" @keydown.escape.window="if (accountOpen) { accountOpen = false; $refs.accountTrigger.focus() }">
                    <button x-ref="accountTrigger" type="button" @click="accountOpen = !accountOpen" @keydown.arrow-down.prevent="accountOpen = true; $nextTick(() => $refs.accountMenu.querySelector('[role=menuitem]')?.focus())" @keydown.arrow-up.prevent="accountOpen = true; $nextTick(() => { const items = $refs.accountMenu.querySelectorAll('[role=menuitem]'); items[items.length - 1]?.focus() })" :aria-expanded="accountOpen.toString()" aria-haspopup="menu" aria-controls="account-menu"
                            class="group inline-flex min-h-11 items-center gap-2 rounded-full border border-white/15 bg-white/[0.04] py-1 pl-1 pr-2.5 text-left transition duration-200 hover:border-amber/50 hover:bg-white/[0.09] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber sm:gap-2.5 sm:pl-1.5 sm:pr-3">
                        <span class="flex size-9 items-center justify-center rounded-full bg-amber text-sm font-bold text-ink">{{ mb_strtoupper(mb_substr($user->name ?: 'K', 0, 1)) }}</span>
                        <span class="flex min-w-0 flex-col leading-tight">
                            <span class="hidden text-[0.65rem] font-medium uppercase tracking-[0.12em] text-white/55 sm:block">{{ __('user.your_account') }}</span>
                            <span class="max-w-16 truncate text-xs font-semibold text-white sm:max-w-32 sm:text-sm">{{ $user->name }}</span>
                        </span>
                        @if (config('kattufy.demo_mode') && ! app()->isProduction())
                            <span class="hidden rounded-full bg-amber/15 px-2 py-1 text-[0.65rem] font-semibold text-amber md:inline-flex">{{ __('ui.demo_mode') }}</span>
                        @endif
                        <x-ki name="chevron-down" class="size-4 text-white/55 transition-transform duration-200 group-hover:text-amber" ::class="accountOpen && 'rotate-180'" />
                    </button>
                    <div id="account-menu" x-ref="accountMenu" @keydown.arrow-down="moveMenu($event)" @keydown.arrow-up="moveMenu($event)" @keydown.home="moveMenu($event)" @keydown.end="moveMenu($event)" x-cloak x-show="accountOpen" x-transition.opacity.duration.150ms
                         class="absolute right-0 top-full mt-2 w-64 overflow-hidden rounded-panel border border-line bg-surface text-fg shadow-raised" role="menu" aria-label="{{ __('user.your_account') }}">
                        <div class="border-b border-line px-4 py-3">
                            <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                            <p class="mt-0.5 text-xs text-fg-subtle">{{ __('ui.role_customer') }}</p>
                        </div>
                        <div class="p-1.5">
                            <a role="menuitem" @click="accountOpen = false" href="{{ lroute('user.profile') }}" @class(['flex min-h-11 items-center gap-3 rounded-control px-3 text-sm transition-colors hover:bg-surface-2 hover:text-fg focus:bg-surface-2 focus:outline-none', 'bg-surface-2 font-semibold text-fg' => request()->routeIs('user.profile')])>
                                <x-ki name="user" class="size-[1.1rem] text-amber-700 dark:text-amber" /> {{ __('user.my_profile') }}
                            </a>
                            <a role="menuitem" @click="accountOpen = false" href="{{ lroute('user.profile') }}?edit=1" class="flex min-h-11 items-center gap-3 rounded-control px-3 text-sm transition-colors hover:bg-surface-2 hover:text-fg focus:bg-surface-2 focus:outline-none">
                                <x-ki name="pencil" class="size-[1.1rem] text-fg-subtle" /> {{ __('user.edit_profile') }}
                            </a>
                            <a role="menuitem" @click="accountOpen = false" href="{{ lroute('user.profile') }}?share=1" class="flex min-h-11 items-center gap-3 rounded-control px-3 text-sm transition-colors hover:bg-surface-2 hover:text-fg focus:bg-surface-2 focus:outline-none">
                                <x-ki name="share" class="size-[1.1rem] text-fg-subtle" /> {{ __('user.share_profile') }}
                            </a>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line p-1.5">
                            @csrf
                            <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
                            <button type="submit" role="menuitem" class="flex min-h-11 w-full items-center gap-3 rounded-control px-3 text-left text-sm font-medium text-danger-text transition-colors hover:bg-danger-soft focus:bg-danger-soft focus:outline-none">
                                <x-ki name="logout" class="size-[1.1rem]" /> {{ __('ui.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endauth

        @if ($areaLabel)
            <span class="hidden rounded-tag border border-amber/40 bg-amber/10 px-2 py-1 text-xs font-semibold text-amber md:inline-block">{{ $areaLabel }}</span>
        @endif

        {{-- Desktop navigation --}}
        @if ($user && $user->isVerified())
            <nav class="ml-4 hidden items-center gap-0.5 xl:flex" aria-label="{{ __('ui.main_navigation') }}">
                @if ($isCustomer)
                <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" class="{{ $navLink }}" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="true">
                        {{ __('ui.find_services') }}
                        <x-ki name="chevron-down" class="size-4 transition-transform" ::class="open && 'rotate-180'" />
                    </button>
                    <div x-cloak x-show="open" x-transition.opacity.scale.origin.top.left.duration.150ms
                         class="absolute left-0 top-full z-40 mt-2 w-[min(44rem,85vw)] rounded-panel border border-line bg-surface p-5 text-fg shadow-raised">
                        <div class="grid grid-cols-3 gap-6">
                            @foreach ($groups as $group)
                                <div>
                                    <a href="{{ lroute('directory.section', $group->slug) }}" class="text-sm font-semibold text-fg hover:text-accent-text">{{ tr_field($group, 'name') }}</a>
                                    <ul class="mt-2.5 space-y-1">
                                        @foreach ($group->categories as $category)
                                            <li>
                                                <a href="{{ lroute('directory.section', $category->slug) }}" class="block rounded-md px-2 py-1.5 text-[0.925rem] text-fg-muted hover:bg-surface-2 hover:text-fg">{{ tr_field($category, 'name') }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 border-t border-line pt-3 text-sm text-fg-subtle">{{ __('ui.live_in_chennai') }}</p>
                    </div>
                </div>
                <a href="{{ lroute('search') }}" class="{{ $navLink }}">{{ __('ui.search') }}</a>
                @endif
                <a href="{{ lroute('guides') }}" class="{{ $navLink }}">{{ __('ui.guides') }}</a>
                <a href="{{ $role === Role::Customer ? lroute('user.faqs') : lroute('faq') }}" class="{{ $navLink }}">{{ __('ui.faq_short') }}</a>
            </nav>
        @endif

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            <x-lang-switch class="hidden sm:inline-flex" />
            <x-theme-toggle />

            @guest
                <a href="{{ lroute('login') }}" class="btn btn-sm hidden border border-white/25 text-white hover:border-white/50 hover:bg-white/5 sm:inline-flex">{{ __('ui.login') }}</a>
                <a href="{{ lroute('register') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">{{ __('ui.register') }}</a>
            @endguest

            <button type="button" class="inline-flex size-11 items-center justify-center rounded-control text-white hover:bg-white/10 xl:hidden"
                    @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu">
                <span class="sr-only">{{ __('ui.open_menu') }}</span>
                <x-ki name="menu" class="size-6" x-show="!menu" />
                <x-ki name="x" class="size-6" x-cloak x-show="menu" />
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobile-menu" x-cloak x-show="menu" x-collapse class="border-t border-white/10 xl:hidden">
        <div class="container-page space-y-4 py-4">
            @auth
                <div class="flex items-center gap-3 rounded-panel bg-white/5 p-3">
                    <span class="flex size-10 items-center justify-center rounded-md bg-amber font-bold text-ink">{{ mb_strtoupper(mb_substr($user->name ?: 'K', 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $user->name }}</p>
                        <p class="text-sm text-white/60">{{ $role ? __('ui.role_'.$role->value) : '' }} · {{ Phone::mask($user->phone) }}</p>
                    </div>
                </div>
                <nav class="grid gap-1" aria-label="{{ __('ui.main_navigation') }}">
                    <a href="{{ $homeUrl }}" class="flex min-h-12 items-center gap-3 rounded-control px-3 font-medium hover:bg-white/8"><x-ki name="grid" class="text-amber" /> {{ __('ui.my_dashboard') }}</a>
                    @if ($isCustomer)
                        <a href="{{ lroute('user.profile') }}" class="flex min-h-12 items-center gap-3 rounded-control px-3 font-medium hover:bg-white/8"><x-ki name="user" class="text-amber" /> {{ __('user.my_profile') }}</a>
                    @endif
                    @if ($user->isVerified() && $role)
                        @if ($isCustomer)
                            <a href="{{ lroute('search') }}" class="flex min-h-12 items-center gap-3 rounded-control px-3 font-medium hover:bg-white/8"><x-ki name="search" class="text-amber" /> {{ __('ui.find_services') }}</a>
                        @endif
                        <a href="{{ lroute('guides') }}" class="flex min-h-12 items-center gap-3 rounded-control px-3 font-medium hover:bg-white/8"><x-ki name="document" class="text-amber" /> {{ __('ui.guides') }}</a>
                        <a href="{{ $role === Role::Customer ? lroute('user.faqs') : lroute('faq') }}" class="flex min-h-12 items-center gap-3 rounded-control px-3 font-medium hover:bg-white/8"><x-ki name="question" class="text-amber" /> {{ __('ui.faq') }}</a>
                    @endif
                </nav>
            @endauth
            <div class="flex flex-wrap items-center gap-2 border-t border-white/10 pt-4">
                <x-lang-switch full />
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                        @csrf
                        <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
                        <button type="submit" class="btn btn-sm border border-white/25 text-white hover:bg-white/5"><x-ki name="logout" class="size-4" /> {{ __('ui.logout') }}</button>
                    </form>
                @else
                    <a href="{{ lroute('login') }}" class="btn btn-sm ml-auto border border-white/25 text-white">{{ __('ui.login') }}</a>
                    <a href="{{ lroute('register') }}" class="btn btn-primary btn-sm">{{ __('ui.register') }}</a>
                @endauth
            </div>
        </div>
    </div>
</header>
