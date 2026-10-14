<?php

namespace App\Http\Requests\Concerns;

use App\Support\Phone;

trait NormalizesPhone
{
    /** Strip spaces, dashes and a +91 / 0 prefix so "98400 12345" validates as 10 digits. */
    protected function normalizePhoneField(string $field): void
    {
        if ($this->has($field)) {
            $e164 = Phone::normalize($this->input($field));
            $this->merge([$field => $e164 ? Phone::national($e164) : preg_replace('/\s+/', '', (string) $this->input($field))]);
        }
    }

    public function phoneE164(string $field = 'phone'): string
    {
        return (string) Phone::normalize($this->input($field));
    }
}
