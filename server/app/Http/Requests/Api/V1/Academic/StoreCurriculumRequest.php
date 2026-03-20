<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_id' => [
                'required', 'uuid',
                Rule::exists('levels', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'specialization_id' => [
                'nullable', 'uuid',
                Rule::exists('specializations', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'subject_id' => [
                'required', 'uuid',
                Rule::exists('subjects', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'coefficient' => ['required', 'numeric', 'min:0.01'],
            'weekly_hours' => ['required', 'integer', 'min:1'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
