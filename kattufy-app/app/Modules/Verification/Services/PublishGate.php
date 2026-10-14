<?php

namespace App\Modules\Verification\Services;

use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Models\Credential;

/**
 * The Definition of Done a profile must meet before it is Published
 * (Architecture §5). Returns the list of failures; an empty list is a pass.
 */
class PublishGate
{
    /** @return array<string, array{label: string, passed: bool}> */
    public function checklist(Provider $provider): array
    {
        $provider->loadMissing(['categories', 'zones', 'credentials.type', 'consents']);

        $required = $provider->requiredCredentialTypeIds();
        $verifiedTypes = $provider->credentials->filter(fn (Credential $c) => $c->isCurrentlyVerified())->pluck('credential_type_id')->all();
        $addressProof = $provider->addressProof();

        return [
            'consent' => ['label' => 'Consent recorded', 'passed' => $provider->consent_at !== null && $provider->consents->whereNull('withdrawn_at')->isNotEmpty()],
            'phone' => ['label' => 'Business phone verified by OTP', 'passed' => $provider->phone_verified_at !== null],
            'type' => ['label' => 'Provider type chosen', 'passed' => $provider->provider_type_id !== null],
            'category' => ['label' => 'At least one category', 'passed' => $provider->categories->isNotEmpty()],
            'zones' => ['label' => 'Service zones chosen', 'passed' => $provider->zones->isNotEmpty()],
            'credentials' => ['label' => 'Required credentials checked for its categories', 'passed' => array_diff($required, $verifiedTypes) === []],
            'address_proof' => ['label' => 'Address proof checked', 'passed' => $addressProof?->isCurrentlyVerified() ?? false],
            'description' => ['label' => 'Description in at least one language', 'passed' => filled($provider->description_en) || filled($provider->description_ta)],
            'portfolio' => ['label' => 'Portfolio status recorded', 'passed' => $provider->portfolio_status !== 'not_recorded'],
            'contact' => ['label' => 'Contact options configured', 'passed' => $provider->allow_call || $provider->allow_whatsapp || $provider->allow_enquiry],
        ];
    }

    /** @return list<string> */
    public function failures(Provider $provider): array
    {
        return array_values(array_map(
            fn ($item) => $item['label'],
            array_filter($this->checklist($provider), fn ($item) => ! $item['passed'])
        ));
    }

    public function passes(Provider $provider): bool
    {
        return $this->failures($provider) === [];
    }
}
