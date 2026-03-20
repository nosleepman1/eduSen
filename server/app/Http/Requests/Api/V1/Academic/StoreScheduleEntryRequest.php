<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timetable_id' => [
                'required', 'uuid',
                Rule::exists('timetables', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'teaching_assignment_id' => [
                'required', 'uuid',
                Rule::exists('teaching_assignments', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'room' => ['nullable', 'string', 'max:255'],
        ];
    }
}
