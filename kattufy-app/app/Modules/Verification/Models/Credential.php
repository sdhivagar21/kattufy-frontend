<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use App\Modules\Providers\Models\Media;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Enums\CredentialStatus;
use App\Support\Concerns\HasUserAttribution;
use App\Support\Mask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * One row per credential a provider holds, never a single "verified" flag.
 * The document is a private media row (collection credential_document).
 */
class Credential extends Model
{
    use HasUserAttribution;

    protected $guarded = ['id', 'status', 'verified_at', 'verified_by'];

    protected $hidden = ['credential_number'];

    protected function casts(): array
    {
        return [
            'status' => CredentialStatus::class,
            'credential_number' => 'encrypted',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'expiry_flagged_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CredentialType::class, 'credential_type_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(IssuingAuthority::class, 'issuing_authority_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function document(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where('collection', Media::COLLECTION_DOCUMENT)->latestOfMany();
    }

    public function maskedNumber(): string
    {
        return Mask::identifier($this->credential_number);
    }

    public function isCurrentlyVerified(): bool
    {
        return $this->status === CredentialStatus::Verified
            && $this->verified_at !== null
            && ($this->expiry_date === null || $this->expiry_date->isFuture());
    }
}
