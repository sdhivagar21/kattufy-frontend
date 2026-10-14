@use('App\Modules\Accounts\Enums\OtpPurpose')
{{-- Code entry for login, registration and a provider's business phone. --}}
<x-layouts.auth :title="__('auth.verify_title')">
    @if ($purpose === OtpPurpose::Register)
        <x-stepper :steps="[__('auth.step_details'), __('auth.step_verify'), __('auth.step_role')]" :current="2" class="mb-8" />
    @endif

    <div class="mb-7">
        <span class="flex size-12 items-center justify-center rounded-2xl bg-accent-soft text-amber-700 ring-1 ring-amber/30 dark:text-amber"><x-ki name="phone" class="size-6" /></span>
        <h1 class="type-display mt-5 text-[1.9rem] sm:text-[2.2rem] ta:text-[1.55rem]">
            {{ $purpose === OtpPurpose::ProviderPhone ? __('auth.verify_business_heading') : __('auth.verify_heading') }}
        </h1>
        <p class="mt-3 text-fg-muted">
            {{ config('kattufy.demo_mode') && ! app()->isProduction() ? __('auth.verify_intro_demo') : __('auth.verify_intro') }}
            <span class="font-semibold whitespace-nowrap text-fg" lang="en">{{ $maskedPhone }}</span>
        </p>
        @if ($purpose !== OtpPurpose::ProviderPhone)
            <a href="{{ $purpose === OtpPurpose::Register ? lroute('register') : lroute('login') }}" class="link mt-2 inline-block text-sm">{{ __('auth.change_number') }}</a>
        @endif
    </div>

    <form method="POST" action="{{ lroute('verify.store') }}" novalidate
          x-data="otpForm({ expiresIn: {{ $expiresIn }}, resendIn: {{ $resendIn }}, demoCode: @js(app()->isProduction() ? null : $demoCode) })"
          @submit="submitting = true" class="space-y-5">
        @csrf
        @include('auth.partials.code-input', ['label' => __('auth.code_from_sms')])

        <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="submitting || code.length !== 6">
            {{ __('auth.verify_continue') }} <x-ki name="arrow-right" class="size-5" />
        </button>
    </form>

    <form method="POST" action="{{ lroute('verify.resend') }}" class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm"
          x-data="otpForm({ resendIn: {{ $resendIn }} })">
        @csrf
        <p class="text-fg-subtle">{{ __('auth.resend_limit') }}</p>
        <button type="submit" class="btn btn-ghost btn-sm" :disabled="resendIn > 0">
            <span x-show="resendIn > 0">{{ __('auth.resend_in') }} <span x-text="clock(resendIn)" class="tabular-nums"></span></span>
            <span x-show="resendIn === 0">{{ __('auth.resend') }}</span>
        </button>
    </form>
</x-layouts.auth>
