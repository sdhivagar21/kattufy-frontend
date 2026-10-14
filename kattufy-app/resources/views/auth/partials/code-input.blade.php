{{-- One 6-digit field that accepts typing, pasting and SMS autofill. Used inside an otpForm. --}}
<div>
    <label for="code" class="field-label">{{ $label }}</label>
    <input id="code" name="code" x-ref="otp" x-model="code" @input="clean()"
           type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="6" required
           aria-describedby="code-help code-error" @error('code') aria-invalid="true" @enderror
           class="input h-16 text-center font-mono text-[1.9rem] font-bold tracking-[0.55em] indent-[0.55em] tabular-nums"
           placeholder="••••••">

    @error('code')
        <p id="code-error" class="field-error" role="alert"><x-ki name="alert" class="mt-0.5 size-4" /> <span>{{ $message }}</span></p>
    @enderror

    <div id="code-help" class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-sm">
        <p class="text-fg-subtle" x-show="expiresIn > 0">{{ __('auth.code_expires_in') }} <span class="font-semibold text-fg tabular-nums" x-text="clock(expiresIn)"></span></p>
        <p class="font-medium text-danger-text" x-cloak x-show="expiresIn === 0">{{ __('auth.code_expired_short') }}</p>
        <button type="button" x-cloak x-show="demoCode" @click="fillDemo()" class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-semibold text-accent-text hover:bg-accent-soft">
            <x-ki name="sparkle" class="size-4" /> {{ __('auth.fill_demo_code') }} <span class="font-mono" x-text="demoCode"></span>
        </button>
    </div>

    <x-demo-note class="mt-4" :code="$demoCode ?? null" />
</div>
