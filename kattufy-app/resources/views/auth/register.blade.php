{{-- Step 3: new accounts. Details are saved first, then the mobile number is confirmed. --}}
<x-layouts.auth :title="__('auth.register_title')">
    <x-stepper :steps="[__('auth.step_details'), __('auth.step_verify'), __('auth.step_role')]" :current="1" class="mb-8" />

    <div class="mb-7">
        <p class="eyebrow">{{ __('auth.register_eyebrow') }}</p>
        <h1 class="type-display mt-2 text-[1.9rem] sm:text-[2.25rem] ta:text-[1.55rem] sm:ta:text-[1.8rem]">{{ __('auth.register_heading') }}</h1>
        <p class="mt-3 text-fg-muted">{{ __('auth.register_intro') }}</p>
    </div>

    <form method="POST" action="{{ lroute('register.store') }}" x-data="submitOnce" @submit="submit" novalidate class="space-y-5">
        @csrf
        <x-field name="name" :label="__('auth.full_name')" :hint="__('auth.name_hint')" required>
            <input id="name" name="name" type="text" autocomplete="name" required maxlength="150" value="{{ old('name') }}"
                   class="input" aria-describedby="name-hint name-error" @error('name') aria-invalid="true" @enderror>
        </x-field>

        <x-field name="phone" :label="__('auth.mobile_number')" :hint="__('auth.mobile_hint')" required>
            <div class="flex">
                <span class="inline-flex items-center rounded-l-control border border-r-0 border-line-strong bg-surface-2 px-3.5 font-medium text-fg-muted" aria-hidden="true">+91</span>
                <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="14" required value="{{ old('phone') }}"
                       placeholder="98400 12345" class="input rounded-l-none" aria-describedby="phone-hint phone-error" @error('phone') aria-invalid="true" @enderror>
            </div>
        </x-field>

        <x-field name="email" :label="__('auth.email_address')" :hint="__('auth.email_register_hint')" required>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="255" value="{{ old('email') }}"
                   placeholder="you@example.com" class="input" aria-describedby="email-hint email-error" @error('email') aria-invalid="true" @enderror>
        </x-field>

        @include('auth.partials.turnstile')

        <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="busy">
            <span x-show="!busy">{{ __('auth.register_submit') }}</span>
            <span x-cloak x-show="busy">{{ __('ui.please_wait') }}</span>
        </button>

        <x-demo-note :code="config('kattufy.demo_codes.mobile')">{{ __('auth.demo_fictional_details') }}</x-demo-note>
    </form>

    <ul class="mt-7 space-y-2 border-t border-line pt-6 text-sm text-fg-subtle">
        <li class="flex items-start gap-2"><x-ki name="eye-slash" class="mt-0.5 size-4" /> {{ __('auth.register_privacy') }}</li>
        <li class="flex items-start gap-2"><x-ki name="lock" class="mt-0.5 size-4" /> {{ __('auth.no_password') }}</li>
    </ul>

    <p class="mt-6 text-center text-fg-muted">{{ __('auth.already_registered') }} <a href="{{ lroute('login') }}" class="link">{{ __('ui.login') }}</a></p>
</x-layouts.auth>
