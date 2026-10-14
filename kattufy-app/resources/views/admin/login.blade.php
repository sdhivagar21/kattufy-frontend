{{-- Super Admin sign-in, step 1 of 3: registered staff mobile number and email. --}}
<x-layouts.auth :title="__('auth.admin_title')" variant="admin">
    @include('admin.partials.steps', ['step' => 1])

    <div class="mb-7">
        <p class="eyebrow">{{ __('auth.admin_eyebrow') }}</p>
        <h1 class="type-display mt-2 text-[1.9rem] sm:text-[2.2rem] ta:text-[1.55rem]">{{ __('auth.admin_heading') }}</h1>
        <p class="mt-3 text-fg-muted">{{ __('auth.admin_intro') }}</p>
    </div>

    <form method="POST" action="{{ lroute('admin.login.store') }}" x-data="submitOnce" @submit="submit" novalidate class="space-y-5">
        @csrf
        <x-field name="phone" :label="__('auth.admin_mobile')" required>
            <div class="flex">
                <span class="inline-flex items-center rounded-l-control border border-r-0 border-line-strong bg-surface-2 px-3.5 font-medium text-fg-muted" aria-hidden="true">+91</span>
                <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="14" required
                       value="{{ old('phone', app()->isProduction() ? '' : ($prefill['phone'] ?? '')) }}" class="input rounded-l-none" aria-describedby="phone-error" @error('phone') aria-invalid="true" @enderror>
            </div>
        </x-field>

        <x-field name="email" :label="__('auth.admin_email')" required>
            <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', app()->isProduction() ? '' : ($prefill['email'] ?? '')) }}"
                   class="input" aria-describedby="email-error" @error('email') aria-invalid="true" @enderror>
        </x-field>

        @include('auth.partials.turnstile')

        <button type="submit" class="btn btn-dark btn-lg w-full" :disabled="busy">
            <x-ki name="lock" class="size-5" /> {{ __('auth.admin_send_mobile_code') }}
        </button>

        @if ($demo && ! app()->isProduction())
            <div class="space-y-3 border-t border-line pt-5">
                <x-demo-note>{{ __('auth.admin_demo_hint', ['mobile' => $demo['phone'], 'email' => $demo['email']]) }}</x-demo-note>
                @unless ($prefill)
                    <a href="{{ lroute('admin.login') }}?demo=admin" class="btn btn-secondary btn-sm w-full">{{ __('auth.admin_demo_fill') }}</a>
                @endunless
            </div>
        @endif
    </form>

    <p class="mt-6 text-center text-fg-muted"><a href="{{ lroute('login') }}" class="link">{{ __('auth.back_to_login') }}</a></p>
</x-layouts.auth>
