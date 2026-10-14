<?php

namespace App\Http\Requests;

use App\Modules\Directory\Models\Category;
use App\Modules\Geography\Models\Locality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnquiryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'locality_id' => ['required', 'integer', Rule::exists(Locality::class, 'id')],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'budget' => ['nullable', 'string', 'max:50'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'source' => ['nullable', Rule::in(['no_results', 'coming_soon', 'direct'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('auth.full_name'),
            'category_id' => __('directory.service'),
            'locality_id' => __('directory.area'),
            'message' => __('directory.enquiry_message'),
            'budget' => __('directory.budget'),
            'preferred_date' => __('directory.preferred_date'),
        ];
    }
}
