<?php

namespace App\Modules\Providers\Services;

use App\Models\User;
use App\Modules\Geography\Models\City;
use App\Modules\Geography\Services\LocationResolver;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Media;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Enums\CredentialStatus;
use App\Modules\Verification\Models\Consent;
use App\Modules\Verification\Models\Credential;
use App\Modules\Verification\Models\CredentialType;
use App\Modules\Verification\Services\LifecycleService;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provider self-registration (/provider/register/). Creates the provider as
 * Found, records consent and moves it to Consented, stores the business
 * details and credentials, and on submit moves it to Under review.
 */
class ProviderSubmissionService
{
    public function __construct(
        private readonly LifecycleService $lifecycle,
        private readonly MediaService $media,
        private readonly LocationResolver $locations,
        private readonly CompletenessCalculator $completeness,
    ) {}

    /**
     * @param  array{business_name: string, address: string, pincode: string, business_phone: string, business_email: string, category_id: int, id_type: string, id_number: string, certificate_type?: ?string}  $data
     */
    public function save(User $user, array $data, ?UploadedFile $addressProof = null, ?UploadedFile $certificate = null): Provider
    {
        return DB::transaction(function () use ($user, $data, $addressProof, $certificate) {
            // Locked so a double submit cannot create two businesses for one account.
            $provider = Provider::where('user_id', $user->id)->orderBy('id')->lockForUpdate()->first()
                ?? $this->create($user, $data);

            $phone = Phone::normalize($data['business_phone']);

            if ($phone !== $provider->phone) {
                // A number is verified only when it is the account's own OTP-verified number;
                // any other number gets its own OTP before submission.
                $provider->phone_verified_at = ($phone === $user->phone && $user->isVerified()) ? now() : null;
            }

            $provider->fill([
                'business_name_en' => trim($data['business_name']),
                'address' => trim($data['address']),
                'pincode' => $data['pincode'],
                'phone' => $phone,
                'email' => Str::lower(trim($data['business_email'])),
                'locality_id' => $this->locations->fromPincode($data['pincode'])?->id,
            ]);
            $provider->save();

            $provider->categories()->sync([(int) $data['category_id'] => ['is_primary' => true]]);

            $this->saveIdentity($provider, $data['id_type'], $data['id_number'] ?? null);

            if ($addressProof) {
                $this->replaceDocument($provider, 'address-proof', null, $addressProof);
            }

            if ($certificate && filled($data['certificate_type'] ?? null)) {
                $this->replaceCertificate($provider, $data['certificate_type'], $certificate);
            }

            $provider->forceFill(['completeness_score' => $this->completeness->score($provider->fresh())])->save();

            // Keep the account's cached relation in step with the new record.
            $user->setRelation('provider', $provider->fresh());

            return $user->provider;
        });
    }

    /** Hand the submission to the verification team. */
    public function submit(Provider $provider): void
    {
        $this->lifecycle->transition($provider, ProviderStatus::UnderReview, null, ['source' => 'provider_submission']);
    }

    public function markPhoneVerified(Provider $provider): void
    {
        $provider->forceFill(['phone_verified_at' => now()])->save();
    }

    private function create(User $user, array $data): Provider
    {
        $provider = new Provider([
            'user_id' => $user->id,
            'business_name_en' => trim($data['business_name']),
            'slug' => $this->uniqueSlug($data['business_name']),
            'city_id' => City::launch()?->id,
            'source' => 'self_registration',
            'allow_enquiry' => true,
        ]);
        $provider->forceFill(['claimed_at' => now()]);
        $provider->save();

        Consent::create([
            'provider_id' => $provider->id,
            'consented_at' => now(),
            'method' => 'web_form',
            'scope' => ['business_name', 'description', 'locality', 'portfolio', 'contact_via_kattufy'],
            'policy_version' => 'v1',
            'given_by_name' => $user->name,
        ]);
        $provider->forceFill(['consent_at' => now()])->save();

        $this->lifecycle->transition($provider, ProviderStatus::Consented, null, ['source' => 'self_registration']);

        return $provider;
    }

    private function saveIdentity(Provider $provider, string $typeSlug, ?string $number): void
    {
        $type = CredentialType::where('slug', $typeSlug)->firstOrFail();
        $number = Str::upper(preg_replace('/\s+/', '', (string) $number));

        $existing = $provider->credentials()->whereIn('credential_type_id', CredentialType::whereIn('slug', CredentialType::slugsIn('identity'))->pluck('id'))->first();

        // Empty number = keep the saved one (the form never shows it again).
        if ($existing && $existing->credential_type_id === $type->id && ($number === '' || $existing->credential_number === $number)) {
            return;
        }

        $credential = $existing ?? new Credential(['provider_id' => $provider->id]);
        $credential->fill([
            'credential_type_id' => $type->id,
            'issuing_authority_id' => $type->issuing_authority_id,
            'credential_number' => $number,
            'holder_name' => $provider->business_name_en,
        ]);
        $credential->forceFill(['status' => CredentialStatus::Submitted, 'verified_at' => null, 'verified_by' => null]);
        $credential->save();
    }

    private function replaceCertificate(Provider $provider, string $typeSlug, UploadedFile $file): void
    {
        $certificateTypeIds = CredentialType::whereIn('slug', CredentialType::optionalCertificateSlugs())->pluck('id');
        $existing = $provider->credentials()->whereIn('credential_type_id', $certificateTypeIds)->first();

        $this->replaceDocument($provider, $typeSlug, $existing, $file);
    }

    private function replaceDocument(Provider $provider, string $typeSlug, ?Credential $existing, UploadedFile $file): void
    {
        $type = CredentialType::where('slug', $typeSlug)->firstOrFail();
        $existing ??= $provider->credentials()->where('credential_type_id', $type->id)->first();

        $credential = $existing ?? new Credential(['provider_id' => $provider->id]);
        $credential->fill([
            'credential_type_id' => $type->id,
            'issuing_authority_id' => $type->issuing_authority_id,
            'holder_name' => $provider->business_name_en,
        ]);
        $credential->forceFill(['status' => CredentialStatus::Submitted, 'verified_at' => null, 'verified_by' => null]);
        $credential->save();

        // Keep the history: the previous file is soft-deleted, not erased.
        $credential->morphMany(Media::class, 'mediable')->where('collection', Media::COLLECTION_DOCUMENT)->get()->each->delete();

        $this->media->storePrivateDocument($file, $credential);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'provider';
        $citySlugs = City::pluck('slug')->all();
        $slug = $base;
        $i = 2;

        while (in_array($slug, $citySlugs, true) || Provider::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
