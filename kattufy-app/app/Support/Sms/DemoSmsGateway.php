<?php

namespace App\Support\Sms;

/** Demo mode: nothing is sent. The fixed demo code is used instead. */
class DemoSmsGateway implements SmsGateway
{
    public function send(string $e164, string $message): bool
    {
        return false;
    }
}
