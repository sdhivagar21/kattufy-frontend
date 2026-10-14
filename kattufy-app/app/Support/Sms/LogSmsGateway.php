<?php

namespace App\Support\Sms;

use Illuminate\Support\Facades\Log;

/** Local development only: the message goes to storage/logs, not to a phone. */
class LogSmsGateway implements SmsGateway
{
    public function send(string $e164, string $message): bool
    {
        Log::channel(config('logging.default'))->info('[SMS not sent: log driver] '.$e164.': '.$message);

        return false;
    }
}
