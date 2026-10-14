<?php

namespace App\Modules\Verification\Models;

use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kinds of credential. Aadhaar is never a type, so it cannot be selected or
 * uploaded (Architecture §7).
 *
 * Groups (by slug) used by the provider registration form:
 *   identity     – government ID number collected at registration
 *   address      – the address proof document (required)
 *   certificate  – the optional business certificate
 *   professional – category registrations checked against a public register
 *                  (shown on profiles as "Credentials checked")
 */
class CredentialType extends Model
{
    use HasUserAttribution;

    public const GROUPS = [
        'pan-card' => 'identity',
        'voter-id' => 'identity',
        'driving-licence' => 'identity',
        'passport' => 'identity',
        'address-proof' => 'address',
        'company-registration-certificate' => 'certificate',
        'gst-certificate' => 'certificate',
        'other-government-certificate' => 'certificate',
        'coa-registration' => 'professional',
        'gcc-contractor-registration' => 'professional',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['has_expiry' => 'boolean', 'requires_document' => 'boolean', 'is_active' => 'boolean'];
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(IssuingAuthority::class, 'issuing_authority_id');
    }

    public function getGroupAttribute(): ?string
    {
        return self::GROUPS[$this->slug] ?? null;
    }

    public static function slugsIn(string $group): array
    {
        return array_keys(array_filter(self::GROUPS, fn ($g) => $g === $group));
    }

    /**
     * What the optional "certificate" upload on the registration form may be:
     * company / GST / other government certificates, or the professional
     * registration a category requires (COA, GCC).
     */
    public static function optionalCertificateSlugs(): array
    {
        return array_merge(self::slugsIn('certificate'), self::slugsIn('professional'));
    }
}
