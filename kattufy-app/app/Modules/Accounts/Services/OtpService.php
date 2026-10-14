<?php

namespace App\Modules\Accounts\Services;

use App\Modules\Accounts\Enums\OtpCheck;
use App\Modules\Accounts\Enums\OtpPurpose;
use App\Modules\Accounts\Exceptions\OtpThrottled;
use App\Modules\Accounts\Models\OtpCode;
use App\Support\Sms\SmsGateway;
use Illuminate\Support\Facades\Mail;

/**
 * One-time codes (Architecture §6B, §7): hashed 6-digit codes, 5-minute
 * expiry, 5 attempts, a 60-second resend gap, 5 sends per phone and 10 per IP
 * an hour, and a daily cap on real SMS.
 *
 * In demo mode the code is a fixed, documented value and nothing is sent.
 */
class OtpService
{
    public function __construct(private readonly SmsGateway $sms) {}

    public static function demo(): bool
    {
        return (bool) config('kattufy.demo_mode');
    }

    /**
     * Create and deliver a code. $email is the destination for the admin's
     * email factor; the row is still keyed by the account's phone.
     *
     * @return bool true when the code was actually delivered (never in demo mode)
     *
     * @throws OtpThrottled
     */
    public function send(string $phone, OtpPurpose $purpose, string $ip, ?string $email = null): bool
    {
        $this->guardLimits($phone, $purpose, $ip);

        $code = $this->newCode($purpose);

        $otp = OtpCode::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => $this->hash($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(config('kattufy.otp.ttl_minutes')),
            'ip_address' => $ip,
        ]);

        $delivered = $this->deliver($purpose, $phone, $email, $code);

        if ($delivered) {
            $otp->forceFill(['sent_at' => now()])->save();
        }

        return $delivered;
    }

    public function check(string $phone, OtpPurpose $purpose, string $code): OtpCheck
    {
        $otp = $this->latest($phone, $purpose);

        if (! $otp || $otp->consumed_at) {
            return OtpCheck::Missing;
        }

        if ($otp->isLocked()) {
            return OtpCheck::Locked;
        }

        if ($otp->isExpired()) {
            return OtpCheck::Expired;
        }

        if (! hash_equals($otp->code_hash, $this->hash(trim($code)))) {
            $otp->increment('attempts');

            return $otp->isLocked() ? OtpCheck::Locked : OtpCheck::Wrong;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return OtpCheck::Ok;
    }

    public function attemptsLeft(string $phone, OtpPurpose $purpose): int
    {
        $otp = $this->latest($phone, $purpose);

        return $otp ? max(0, config('kattufy.otp.max_attempts') - $otp->attempts) : 0;
    }

    /** Seconds until the current code expires (0 when there is none). */
    public function secondsLeft(string $phone, OtpPurpose $purpose): int
    {
        $otp = $this->latest($phone, $purpose);

        return $otp && ! $otp->consumed_at ? max(0, (int) now()->diffInSeconds($otp->expires_at, false)) : 0;
    }

    /** Seconds before another code may be requested. */
    public function resendWait(string $phone, OtpPurpose $purpose): int
    {
        $otp = $this->latest($phone, $purpose);

        if (! $otp) {
            return 0;
        }

        return max(0, config('kattufy.otp.resend_gap_seconds') - (int) $otp->created_at->diffInSeconds(now()));
    }

    private function latest(string $phone, OtpPurpose $purpose): ?OtpCode
    {
        return OtpCode::where('phone', $phone)->where('purpose', $purpose->value)->latest('id')->first();
    }

    private function guardLimits(string $phone, OtpPurpose $purpose, string $ip): void
    {
        $wait = $this->resendWait($phone, $purpose);

        if ($wait > 0) {
            throw new OtpThrottled('resend_gap', $wait);
        }

        $hourAgo = now()->subHour();

        $phoneCount = OtpCode::where('phone', $phone)->where('purpose', $purpose->value)->where('created_at', '>=', $hourAgo)->count();
        if ($phoneCount >= config('kattufy.otp.max_per_phone_per_hour')) {
            throw new OtpThrottled('phone_hourly', 3600);
        }

        $ipCount = OtpCode::where('ip_address', $ip)->where('created_at', '>=', $hourAgo)->count();
        if ($ipCount >= config('kattufy.otp.max_per_ip_per_hour')) {
            throw new OtpThrottled('ip_hourly', 3600);
        }

        if (! self::demo() && $purpose->channel() === 'sms') {
            $today = OtpCode::whereNotNull('sent_at')->where('created_at', '>=', now()->startOfDay())->count();
            if ($today >= config('kattufy.otp.daily_sms_cap')) {
                throw new OtpThrottled('daily_cap', (int) now()->diffInSeconds(now()->endOfDay()));
            }
        }
    }

    private function newCode(OtpPurpose $purpose): string
    {
        if (self::demo()) {
            return $purpose->channel() === 'email' ? config('kattufy.demo_codes.email') : config('kattufy.demo_codes.mobile');
        }

        return str_pad((string) random_int(0, 999999), config('kattufy.otp.length'), '0', STR_PAD_LEFT);
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function deliver(OtpPurpose $purpose, string $phone, ?string $email, string $code): bool
    {
        if (self::demo()) {
            return false;
        }

        $minutes = config('kattufy.otp.ttl_minutes');

        if ($purpose->channel() === 'email') {
            if (! $email) {
                return false;
            }

            Mail::raw("Your Kattufy admin sign-in code is {$code}. It expires in {$minutes} minutes. If you did not try to sign in, tell the Super Admin.", function ($message) use ($email) {
                $message->to($email)->subject('Your Kattufy admin sign-in code');
            });

            // The mailer accepted it; with MAIL_MAILER=log nothing really leaves the server.
            return config('mail.default') !== 'log' && config('mail.default') !== 'array';
        }

        return $this->sms->send($phone, "{$code} is your Kattufy code. It expires in {$minutes} minutes. Do not share it.");
    }
}
