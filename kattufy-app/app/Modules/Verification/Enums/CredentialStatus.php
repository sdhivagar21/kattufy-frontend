<?php

namespace App\Modules\Verification\Enums;

enum CredentialStatus: string
{
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return __('status.credential_'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Submitted => 'warning',
            self::Verified => 'success',
            self::Rejected, self::Expired => 'danger',
        };
    }
}
