<?php

namespace App\Support;

/**
 * Masking for summaries and lists, so personal and ID details are not exposed
 * on a shared screen. Full values appear only where a person needs them.
 */
class Mask
{
    /** kavya@example.com → ka•••@example.com */
    public static function email(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 2).'•••@'.$domain;
    }

    /** ABCDE1234F → ••••••234F (last four characters only). */
    public static function identifier(?string $value): string
    {
        $value = preg_replace('/\s+/', '', (string) $value);

        if ($value === '') {
            return '';
        }

        // Last four characters for normal ID lengths; fewer for very short values.
        $visible = mb_strlen($value) >= 8 ? 4 : max(1, intdiv(mb_strlen($value), 3));

        return str_repeat('•', max(4, mb_strlen($value) - $visible)).mb_substr($value, -$visible);
    }
}
