{{--
    Step 2: existing accounts sign in with their registered mobile number and
    email. One entry point for everyone: after the code, the server sends a
    customer, an approved provider or a provider under review to their own page,
    and staff on to their email code and the admin panel.
--}}
<x-layouts.auth :title="__('auth.login_title')">
    {{-- Small brand lock-up (the phone layout already shows one in the top bar) --}}
    <div class="mb-6 hidden lg:block">
        <span class="block dark:hidden"><x-brand tone="light" size="sm" /></span>
        <span class="hidden dark:block"><x-brand tone="dark" size="sm" /></span>
    </div>

    <p class="eyebrow">{{ __('auth.login_eyebrow') }}</p>
    <h1 class="type-display mt-2 text-[1.85rem] sm:text-[2.1rem] ta:text-[1.5rem] sm:ta:text-[1.7rem]">{{ __('auth.login_heading') }}</h1>
    <p class="mt-2.5 text-[0.975rem] leading-relaxed text-fg-muted">{{ __('auth.login_intro') }}</p>

    <form method="POST" action="{{ lroute('login.store') }}" x-data="submitOnce" @submit="submit" novalidate class="mt-6 space-y-4">
        @csrf
        <x-field name="phone" :label="__('auth.mobile_number')" :hint="__('auth.mobile_hint')" required>
            <div class="flex">
                <span class="inline-flex items-center rounded-l-control border border-r-0 border-line-strong bg-surface-2 px-3.5 font-semibold text-fg-muted" aria-hidden="true">+91</span>
                <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="14" required
                       value="{{ old('phone', app()->isProduction() ? '' : ($prefill['phone'] ?? '')) }}" placeholder="98400 12345"
                       class="input rounded-l-none" aria-describedby="phone-hint phone-error" @error('phone') aria-invalid="true" @enderror>
            </div>
        </x-field>

        <x-field name="email" :label="__('auth.email_address')" :hint="__('auth.email_login_hint')" required>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="255"
                   value="{{ old('email', app()->isProduction() ? '' : ($prefill['email'] ?? '')) }}" placeholder="you@example.com"
                   class="input" aria-describedby="email-hint email-error" @error('email') aria-invalid="true" @enderror>
        </x-field>

        @include('auth.partials.turnstile')

        <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="busy">
            <span x-show="!busy">{{ __('auth.send_code') }}</span>
            <span x-cloak x-show="busy">{{ __('ui.please_wait') }}</span>
            <x-ki name="arrow-right" class="size-5" x-show="!busy" />
        </button>

        {{-- Demo builds only: how to try the existing flow. Hidden in production. --}}
        @if (config('kattufy.demo_mode') && ! app()->isProduction())
            <div class="flex items-start gap-3 rounded-control border border-amber/40 bg-ink/[0.045] px-3.5 py-2.5 text-[0.8125rem] leading-relaxed dark:border-amber/30 dark:bg-black/25" role="note">
                <x-ki name="sparkle" class="mt-0.5 size-4 text-amber-700 dark:text-amber" />
                <p class="text-fg-muted"><span class="font-semibold text-fg">{{ __('auth.demo_notice_title') }}</span> {{ __('auth.demo_notice_text') }}</p>
            </div>
        @endif
    </form>

    {{-- Secondary actions --}}
    <div class="mt-6 border-t border-line pt-5 text-sm">
        <p class="flex flex-wrap items-center gap-x-1.5 text-fg-muted">
            {{ __('auth.new_user') }}
            <a href="{{ lroute('register') }}" class="link inline-flex items-center gap-1">{{ __('auth.register_link') }} <x-ki name="arrow-right" class="size-3.5" /></a>
        </p>
    </div>

    <p class="mt-5 flex items-start gap-2.5 rounded-control bg-surface-2 px-3.5 py-2.5 text-[0.8125rem] leading-relaxed text-fg-subtle dark:bg-white/[0.03]">
        <x-ki name="eye-slash" class="mt-0.5 size-4" /> {{ __('auth.login_privacy') }}
    </p>
</x-layouts.auth>
