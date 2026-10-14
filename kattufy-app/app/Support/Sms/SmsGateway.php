<?php

namespace App\Support\Sms;

/**
 * Sends one SMS. Implementations: DemoSmsGateway (sends nothing) and
 * LogSmsGateway (writes to the log, local testing only). A production gateway
 * implements this interface and is selected with KATTUFY_SMS_DRIVER.
 */
interface SmsGateway
{
    /** Returns true only when the message was actually handed to a carrier. */
    public function send(string $e164, string $message): bool;
}
