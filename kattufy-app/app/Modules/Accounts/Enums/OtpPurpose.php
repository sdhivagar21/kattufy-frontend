<?php

namespace App\Modules\Accounts\Enums;

enum OtpPurpose: string
{
    case Login = 'login';
    case Register = 'register';
    case AdminLogin = 'admin_login';
    case AdminEmail = 'admin_email';
    case ProviderPhone = 'provider_phone';

    /** Every purpose goes by SMS except the admin's second factor, which goes by email. */
    public function channel(): string
    {
        return $this === self::AdminEmail ? 'email' : 'sms';
    }
}
