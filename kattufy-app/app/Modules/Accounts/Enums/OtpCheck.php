<?php

namespace App\Modules\Accounts\Enums;

enum OtpCheck: string
{
    case Ok = 'ok';
    case Wrong = 'wrong';
    case Expired = 'expired';
    case Locked = 'locked';
    case Missing = 'missing';
}
