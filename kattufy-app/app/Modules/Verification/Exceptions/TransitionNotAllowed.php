<?php

namespace App\Modules\Verification\Exceptions;

use RuntimeException;

class TransitionNotAllowed extends RuntimeException
{
    /** @param list<string> $failures */
    public function __construct(string $message, public readonly array $failures = [])
    {
        parent::__construct($message);
    }
}
