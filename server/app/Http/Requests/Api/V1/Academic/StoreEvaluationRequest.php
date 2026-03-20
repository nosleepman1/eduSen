<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_period_id' => [
                'required', 'uuid',
                Rule::exists('academic_periods', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'teaching_assignment_id' => [
                'required', 'uuid',
                Rule::exists('teaching_assignments', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'lesson_session_id' => [
                'nullable', 'uuid',
                Rule::exists('lesson_sessions', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'type' => ['required', Rule::in(['DEVOIR', 'EXAMEN'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'evaluation_date' => ['required', 'date'],
            'max_score' => ['required', 'numeric', 'min:0.01'],
            'coefficient' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
