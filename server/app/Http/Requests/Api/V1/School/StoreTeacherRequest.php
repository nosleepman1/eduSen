<?php

namespace App\Http\Requests\Api\V1\School;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personnel_id' => [
                'required', 'uuid',
                Rule::exists('personnel', 'id')->where('school_id', TenantHelper::currentSchoolId()),
                Rule::unique('teachers', 'personnel_id'),
            ],
            'speciality' => ['required', 'string', 'max:255'],
            'diploma' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
        ];
    }
}
