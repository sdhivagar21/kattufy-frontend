<?php

namespace App\Modules\Accounts\Support;

use App\Modules\Accounts\Enums\OtpPurpose;

/**
 * Which code-entry step this browser is in, kept server-side in the session
 * (never in a form field the visitor could change).
 */
class PendingVerification
{
    private const KEY = 'pending_verification';

    public static function start(OtpPurpose $purpose, string $phone, ?int $userId = null, array $extra = []): void
    {
        session([self::KEY => array_merge($extra, [
            'purpose' => $purpose->value,
            'phone' => $phone,
            'user_id' => $userId,
            'started_at' => now()->timestamp,
        ])]);
    }

    public static function get(): ?array
    {
        $flow = session(self::KEY);

        // A pending step older than an hour is abandoned.
        if (! $flow || now()->timestamp - ($flow['started_at'] ?? 0) > 3600) {
            return null;
        }

        return $flow;
    }

    public static function purpose(): ?OtpPurpose
    {
        $flow = self::get();

        return $flow ? OtpPurpose::tryFrom($flow['purpose']) : null;
    }

    public static function is(OtpPurpose ...$purposes): bool
    {
        return in_array(self::purpose(), $purposes, true);
    }

    public static function forget(): void
    {
        session()->forget(self::KEY);
    }
}
