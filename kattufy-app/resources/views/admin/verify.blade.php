{{-- Super Admin sign-in, steps 2 and 3: a mobile code, then a separate email code. --}}
<x-layouts.auth :title="$channel === 'mobile' ? __('auth.admin_mobile_title') : __('auth.admin_email_title')" variant="admin">
    @include('admin.partials.steps', ['step' => $step])

    <div class="mb-7">
        <span class="flex size-12 items-center justify-center rounded-2xl bg-ink text-amber dark:bg-white/10"><x-ki :name="$channel === 'mobile' ? 'phone' : 'mail'" class="size-6" /></span>
        <h1 class="type-display mt-5 text-[1.9rem] sm:text-[2.2rem] ta:text-[1.55rem]">{{ $channel === 'mobile' ? __('auth.admin_mobile_heading') : __('auth.admin_email_heading') }}</h1>
        <p class="mt-3 text-fg-muted">
            {{ config('kattufy.demo_mode') && ! app()->isProduction()
                ? ($channel === 'mobile' ? __('auth.admin_mobile_intro_demo') : __('auth.admin_email_intro_demo'))
                : ($channel === 'mobile' ? __('auth.admin_mobile_intro') : __('auth.admin_email_intro')) }}
            <span class="font-semibold whitespace-nowrap text-fg" lang="en">{{ $destination }}</span>
        </p>
    </div>

    <form method="POST" action="{{ $action }}" novalidate x-data="otpForm({ expiresIn: {{ $expiresIn }}, resendIn: {{ $resendIn }}, demoCode: @js(app()->isProduction() ? null : $demoCode) })"
          @submit="submitting = true" class="space-y-5">
        @csrf
        @include('auth.partials.code-input', ['label' => $channel === 'mobile' ? __('auth.admin_mobile_code') : __('auth.admin_email_code')])

        <button type="submit" class="btn btn-dark btn-lg w-full" :disabled="submitting || code.length !== 6">
            {{ $channel === 'mobile' ? __('auth.admin_verify_mobile') : __('auth.admin_verify_email') }}
        </button>
    </form>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm">
        <a href="{{ lroute('admin.login') }}" class="link">{{ __('auth.start_again') }}</a>
        <form method="POST" action="{{ lroute('admin.resend') }}" x-data="otpForm({ resendIn: {{ $resendIn }} })">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm" :disabled="resendIn > 0">
                <span x-show="resendIn > 0">{{ __('auth.resend_in') }} <span x-text="clock(resendIn)" class="tabular-nums"></span></span>
                <span x-show="resendIn === 0">{{ __('auth.resend') }}</span>
            </button>
        </form>
    </div>
</x-layouts.auth>
