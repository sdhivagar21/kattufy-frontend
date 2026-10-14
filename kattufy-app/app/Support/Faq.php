<?php

namespace App\Support;

/** Questions and answers, from lang/{locale}/faq.php so both languages stay in step. */
class Faq
{
    /** @return list<array{q: string, a: string}> */
    public static function items(): array
    {
        return array_values(trans('faq.items'));
    }
}
