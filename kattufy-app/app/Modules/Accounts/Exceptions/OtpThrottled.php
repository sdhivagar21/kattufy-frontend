<?php

namespace App\Modules\Accounts\Exceptions;

use RuntimeException;

/** A send limit was hit. $reason is a key under lang/{locale}/auth.php "throttle". */
class OtpThrottled extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $retryAfterSeconds)
    {
        parent::__construct("OTP throttled: {$reason}");
    }
}
