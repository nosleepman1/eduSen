<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'main_room' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'end_date_override' => ['nullable', 'date'],
        ];
    }
}
