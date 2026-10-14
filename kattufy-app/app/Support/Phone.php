<?php

namespace App\Support;

/**
 * Indian mobile numbers. Stored in E.164 (+91XXXXXXXXXX), typed as 10 digits.
 */
class Phone
{
    public const PATTERN = '/^[6-9]\d{9}$/';

    /** Accepts "98400 12345", "+91 98400-12345", "098400 12345"; returns +91… or null. */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match(self::PATTERN, $digits) ? '+91'.$digits : null;
    }

    /** +919000000001 → 9000000001 */
    public static function national(?string $e164): string
    {
        return substr((string) $e164, -10);
    }

    /** +919000000001 → +91 90••• ••001 (enough to recognise, not to copy). */
    public static function mask(?string $e164): string
    {
        $n = self::national($e164);

        return strlen($n) === 10 ? '+91 '.substr($n, 0, 2).'••• ••'.substr($n, -3) : '';
    }

    /** +919000000001 → +91 90000 00001 */
    public static function display(?string $e164): string
    {
        $n = self::national($e164);

        return strlen($n) === 10 ? '+91 '.substr($n, 0, 5).' '.substr($n, 5) : (string) $e164;
    }
}
