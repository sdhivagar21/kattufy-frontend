<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Text edits an approved provider can make without a new review (Architecture §5). */
class ProviderProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_email' => mb_strtolower(trim((string) $this->input('business_email'))),
            'pincode' => preg_replace('/\D+/', '', (string) $this->input('pincode')),
        ]);
    }

    public function rules(): array
    {
        return [
            'description_en' => ['nullable', 'string', 'max:2000'],
            'description_ta' => ['nullable', 'string', 'max:2000'],
            'address' => ['required', 'string', 'min:10', 'max:255'],
            'pincode' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
            'business_email' => ['required', 'email:rfc', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['pincode.regex' => __('provider.pincode_invalid')];
    }

    public function attributes(): array
    {
        return [
            'description_en' => __('provider.description_en'),
            'description_ta' => __('provider.description_ta'),
            'address' => __('provider.business_address'),
            'pincode' => __('provider.pincode'),
            'business_email' => __('provider.business_email'),
        ];
    }
}
