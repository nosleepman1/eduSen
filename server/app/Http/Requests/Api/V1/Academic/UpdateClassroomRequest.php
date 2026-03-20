<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassroomRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'main_room' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'end_date_override' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
