<?php

namespace App\Modules\Accounts\Models;

use App\Modules\Accounts\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Model;

/** One row per code sent; the code itself is only stored hashed. */
class OtpCode extends Model
{
    protected $fillable = ['phone', 'purpose', 'code_hash', 'attempts', 'expires_at', 'consumed_at', 'sent_at', 'ip_address'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isLocked(): bool
    {
        return $this->attempts >= config('kattufy.otp.max_attempts');
    }
}
