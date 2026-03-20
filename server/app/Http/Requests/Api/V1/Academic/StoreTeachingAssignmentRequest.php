<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeachingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => [
                'required', 'uuid',
                Rule::exists('classrooms', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'curriculum_id' => [
                'required', 'uuid',
                Rule::exists('curriculums', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'teacher_id' => [
                'required', 'uuid',
                Rule::exists('teachers', 'id'),
            ],
            'role' => ['nullable', Rule::in(['PRINCIPAL', 'SUBSTITUTE', 'CO_TEACHER'])],
            'started_at' => ['nullable', 'date'],
        ];
    }
}
