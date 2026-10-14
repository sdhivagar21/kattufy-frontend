<?php

namespace App\Modules\Providers\Models;

use App\Models\User;
use App\Modules\Directory\Models\Category;
use App\Modules\Directory\Models\ProviderSubtype;
use App\Modules\Directory\Models\ProviderType;
use App\Modules\Directory\Models\Service;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Models\Locality;
use App\Modules\Geography\Models\Zone;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Verification\Enums\VerificationState;
use App\Modules\Verification\Models\Consent;
use App\Modules\Verification\Models\Credential;
use App\Modules\Verification\Models\VerificationEvent;
use App\Modules\Verification\Models\VerificationRequirement;
use App\Support\Concerns\HasUserAttribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A service provider or supplier. The status column changes only through
 * LifecycleService::transition(); it is not mass assignable.
 */
class Provider extends Model
{
    use HasUserAttribution, LogsActivity, SoftDeletes;

    protected $guarded = ['id', 'status', 'status_changed_at', 'published_at', 'reviewed_by', 'last_verified_at'];

    protected function casts(): array
    {
        return [
            'status' => ProviderStatus::class,
            'status_changed_at' => 'datetime',
            'consent_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'claimed_at' => 'datetime',
            'published_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'unreviewed_edits_at' => 'datetime',
            'description_ta_reviewed_at' => 'datetime',
            'working_hours' => 'array',
            'allow_call' => 'boolean',
            'allow_whatsapp' => 'boolean',
            'allow_enquiry' => 'boolean',
            'show_address' => 'boolean',
            'do_not_contact' => 'boolean',
        ];
    }

    protected $attributes = ['status' => 'found'];

    // ----- Relationships ---------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ProviderType::class, 'provider_type_id');
    }

    public function subtype(): BelongsTo
    {
        return $this->belongsTo(ProviderSubtype::class, 'provider_subtype_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'provider_categories')->withPivot('is_primary')->withTimestamps();
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'provider_zones')->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'provider_services')->withTimestamps();
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(VerificationEvent::class)->latest('id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class)->orderBy('sort_order');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    // ----- Scopes ----------------------------------------------------------

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProviderStatus::Published->value);
    }

    // ----- Presentation ----------------------------------------------------

    public function name(): string
    {
        return tr_field($this, 'business_name') ?? '';
    }

    public function initials(): string
    {
        $words = collect(preg_split('/\s+/', trim($this->business_name_en)))->reject(fn ($w) => in_array(strtolower($w), ['and', '&', 'the']));

        return Str::upper($words->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    }

    public function primaryCategory(): ?Category
    {
        return $this->categories->firstWhere('pivot.is_primary', true) ?? $this->categories->first();
    }

    // ----- Badges (computed, never stored: Architecture §5) ----------------

    public function isPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /** Credential type IDs required by this provider's categories (verification_requirements). */
    public function requiredCredentialTypeIds(): array
    {
        return VerificationRequirement::query()
            ->whereIn('category_id', $this->categories->pluck('id'))
            ->where('is_required', true)->where('is_active', true)
            ->pluck('credential_type_id')->unique()->values()->all();
    }

    /** "Credentials checked": every required credential is verified and not expired. */
    public function hasCredentialsChecked(): bool
    {
        $required = $this->requiredCredentialTypeIds();

        if ($required === []) {
            return false;
        }

        $verified = $this->credentials->filter(fn (Credential $c) => $c->isCurrentlyVerified())->pluck('credential_type_id')->all();

        return array_diff($required, $verified) === [];
    }

    /** Professional registrations shown on the public profile (never ID or address documents). */
    public function publicCredentials(): \Illuminate\Support\Collection
    {
        return $this->credentials->filter(fn (Credential $c) => $c->type?->group === 'professional' && $c->isCurrentlyVerified())->values();
    }

    public function hasPortfolioShown(): bool
    {
        $items = $this->relationLoaded('portfolioItems') ? $this->portfolioItems : $this->portfolioItems()->get();

        return $items->where('is_published', true)->isNotEmpty();
    }

    // ----- Verification state seen by the provider -------------------------

    public function verificationState(): VerificationState
    {
        return match ($this->status) {
            ProviderStatus::UnderReview => VerificationState::PendingReview,
            ProviderStatus::Published => VerificationState::Approved,
            ProviderStatus::NeedsUpdate => VerificationState::NeedsUpdate,
            ProviderStatus::Suspended => VerificationState::Suspended,
            ProviderStatus::Consented => $this->wasReturnedByReview() ? VerificationState::ChangesRequested : VerificationState::NotSubmitted,
            default => VerificationState::NotSubmitted,
        };
    }

    /** True when the last lifecycle step sent an Under review submission back. */
    public function wasReturnedByReview(): bool
    {
        $last = $this->latestLifecycleEvent();

        return $last?->from_status === ProviderStatus::UnderReview->value && $last?->to_status === ProviderStatus::Consented->value;
    }

    public function latestLifecycleEvent(): ?VerificationEvent
    {
        return $this->events()->whereNotNull('to_status')->first();
    }

    /** Staff feedback that still applies (the reason on the step that set the current status). */
    public function currentFeedback(): ?VerificationEvent
    {
        $state = $this->verificationState();

        if (! in_array($state, [VerificationState::ChangesRequested, VerificationState::NeedsUpdate, VerificationState::Suspended], true)) {
            return null;
        }

        $event = $this->latestLifecycleEvent();

        return filled($event?->reason) ? $event : null;
    }

    public function submittedAt(): ?\Illuminate\Support\Carbon
    {
        return $this->events()->where('to_status', ProviderStatus::UnderReview->value)->first()?->created_at;
    }

    public function idCredential(): ?Credential
    {
        return $this->credentials->first(fn (Credential $c) => $c->type?->group === 'identity');
    }

    public function addressProof(): ?Credential
    {
        return $this->credentials->first(fn (Credential $c) => $c->type?->slug === 'address-proof');
    }

    public function certificate(): ?Credential
    {
        return $this->credentials->first(fn (Credential $c) => in_array($c->type?->group, ['certificate', 'professional'], true));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('providers')
            ->logOnly(['business_name_en', 'business_name_ta', 'description_en', 'description_ta', 'phone', 'email', 'address', 'pincode', 'locality_id', 'provider_type_id', 'allow_call', 'allow_whatsapp', 'allow_enquiry', 'portfolio_status'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
