<?php

namespace App\Http\Requests\Api\V1\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['PRESENT', 'ABSENT', 'LATE', 'EXCUSED', 'DISPENSED'])],
            'actual_arrival_time' => ['nullable', 'date_format:H:i'],
            'absence_reason' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
