<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'specialization_id' => [
                'sometimes', 'nullable', 'uuid',
                Rule::exists('specializations', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'subject_id' => [
                'sometimes', 'required', 'uuid',
                Rule::exists('subjects', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'coefficient' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'weekly_hours' => ['sometimes', 'required', 'integer', 'min:1'],
            'display_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
