<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Modules\Directory\Models\Category;
use App\Modules\Verification\Models\CredentialType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRegistrationRequest extends FormRequest
{
    use NormalizesPhone;

    /** ID formats; Aadhaar is never accepted (Architecture §7). */
    public const ID_PATTERNS = [
        'pan-card' => '/^[A-Z]{5}[0-9]{4}[A-Z]$/',
        'voter-id' => '/^[A-Z]{3}[0-9]{7}$/',
        'driving-licence' => '/^[A-Z]{2}[0-9]{2}[ -]?[0-9]{4}[0-9]{7}$/',
        'passport' => '/^[A-Z][0-9]{7}$/',
    ];

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneField('business_phone');
        $this->merge([
            'business_name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('business_name'))),
            'business_email' => mb_strtolower(trim((string) $this->input('business_email'))),
            'id_number' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('id_number'))),
            'pincode' => preg_replace('/\D+/', '', (string) $this->input('pincode')),
        ]);
    }

    public function rules(): array
    {
        $provider = $this->user()->provider;
        $submitting = $this->input('action') === 'submit';
        $hasAddressProof = (bool) $provider?->addressProof()?->document;
        $files = ['file', 'mimes:'.implode(',', config('kattufy.uploads.mimes')), 'max:'.config('kattufy.uploads.max_kb')];

        // A saved ID number is never sent back to the browser; leaving the field
        // empty keeps it, unless the ID type is being changed.
        $savedId = $provider?->idCredential();
        $keepSavedId = $savedId && $savedId->type?->slug === $this->input('id_type');

        return [
            'action' => ['required', Rule::in(['save', 'submit'])],
            'business_name' => ['required', 'string', 'min:2', 'max:200'],
            'address' => ['required', 'string', 'min:10', 'max:255'],
            'pincode' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
            'business_phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'business_email' => ['required', 'email:rfc', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'id_type' => ['required', Rule::in(CredentialType::slugsIn('identity'))],
            'id_number' => [$keepSavedId ? 'nullable' : 'required', 'string', 'max:20', function ($attribute, $value, $fail) {
                $pattern = self::ID_PATTERNS[$this->input('id_type')] ?? null;
                if ($pattern && ! preg_match($pattern, $value)) {
                    $fail(__('provider.id_number_format'));
                }
                if (preg_match('/^\d{12}$/', $value)) {
                    $fail(__('provider.no_aadhaar'));
                }
            }],
            'address_proof' => [($submitting && ! $hasAddressProof) ? 'required' : 'nullable', ...$files],
            'certificate_type' => ['nullable', 'required_with:certificate', Rule::in(CredentialType::optionalCertificateSlugs())],
            'certificate' => ['nullable', ...$files],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_phone.regex' => __('auth.phone_invalid'),
            'pincode.regex' => __('provider.pincode_invalid'),
            'address_proof.required' => __('provider.address_proof_required'),
            'address_proof.mimes' => __('provider.file_type'),
            'certificate.mimes' => __('provider.file_type'),
            'address_proof.max' => __('provider.file_size'),
            'certificate.max' => __('provider.file_size'),
            'consent.accepted' => __('provider.consent_required'),
        ];
    }

    public function attributes(): array
    {
        return [
            'business_name' => __('provider.business_name'),
            'address' => __('provider.business_address'),
            'pincode' => __('provider.pincode'),
            'business_phone' => __('provider.business_phone'),
            'business_email' => __('provider.business_email'),
            'category_id' => __('provider.category'),
            'id_type' => __('provider.id_type'),
            'id_number' => __('provider.id_number'),
            'address_proof' => __('provider.address_proof'),
            'certificate_type' => __('provider.certificate_type'),
            'certificate' => __('provider.certificate'),
        ];
    }
}
