{{-- Cloudflare Turnstile before a code is sent. Not shown until TURNSTILE_SITE_KEY / SECRET are set. --}}
@if (\App\Support\Turnstile::enabled())
    <div class="cf-turnstile" data-sitekey="{{ config('kattufy.turnstile.site_key') }}" data-language="{{ app()->getLocale() }}" data-theme="auto"></div>
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
