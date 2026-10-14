<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Accept "123 456" or a pasted code with spaces.
        $this->merge(['code' => preg_replace('/\D+/', '', (string) $this->input('code'))]);
    }

    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }

    public function attributes(): array
    {
        return ['code' => __('auth.code')];
    }
}
