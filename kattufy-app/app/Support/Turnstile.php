<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile, checked server-side before an OTP is sent. Skipped
 * when TURNSTILE_SECRET_KEY is empty (development and demo).
 */
class Turnstile
{
    public static function enabled(): bool
    {
        return filled(config('kattufy.turnstile.secret_key')) && filled(config('kattufy.turnstile.site_key'));
    }

    public static function passes(?string $token, ?string $ip): bool
    {
        if (! self::enabled()) {
            return true;
        }

        if (! $token) {
            return false;
        }

        $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => config('kattufy.turnstile.secret_key'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        return $response->ok() && $response->json('success') === true;
    }
}
