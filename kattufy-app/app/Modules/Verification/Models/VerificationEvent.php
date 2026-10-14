<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use App\Modules\Providers\Models\Provider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/** Append-only history: every lifecycle transition and every credential check. */
class VerificationEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->created_by ??= Auth::id();
        });

        // Append-only.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
