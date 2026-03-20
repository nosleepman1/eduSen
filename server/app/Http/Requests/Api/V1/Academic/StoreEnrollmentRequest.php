<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
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
            'student_id' => [
                'required', 'uuid',
                Rule::exists('students', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'enrolled_at' => ['nullable', 'date'],
        ];
    }
}
