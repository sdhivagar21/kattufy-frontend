<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use NormalizesPhone;

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneField('phone');
        $this->merge([
            'name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('name'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150', 'regex:/^[\pL\pM\s.\'-]+$/u'],
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('auth.phone_invalid'),
            'name.regex' => __('auth.name_invalid'),
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('auth.full_name'), 'phone' => __('auth.mobile_number'), 'email' => __('auth.email_address')];
    }
}
