<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => [
                'required', 'uuid',
                Rule::exists('evaluations', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'enrollment_id' => [
                'required', 'uuid',
                Rule::exists('enrollments', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'status' => ['nullable', Rule::in(['PENDING', 'GRADED', 'ABSENT', 'DISPENSED'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
