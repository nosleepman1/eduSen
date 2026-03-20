<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required', 'uuid',
                Rule::exists('academic_years', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'specialization_id' => [
                'nullable', 'uuid',
                Rule::exists('specializations', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('levels', 'name')->where('academic_year_id', $this->input('academic_year_id')),
            ],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('levels', 'code')->where('academic_year_id', $this->input('academic_year_id')),
            ],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
