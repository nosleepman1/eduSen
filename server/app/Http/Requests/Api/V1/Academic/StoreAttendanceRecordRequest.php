<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_session_id' => [
                'required', 'uuid',
                Rule::exists('lesson_sessions', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'enrollment_id' => [
                'required', 'uuid',
                Rule::exists('enrollments', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'status' => ['nullable', Rule::in(['PRESENT', 'ABSENT', 'LATE', 'EXCUSED', 'DISPENSED'])],
            'actual_arrival_time' => ['nullable', 'date_format:H:i'],
            'absence_reason' => ['nullable', 'string', 'max:255'],
            'justification' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
