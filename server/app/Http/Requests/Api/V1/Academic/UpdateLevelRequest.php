<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $level = $this->route('level');

        return [
            'specialization_id' => [
                'sometimes', 'nullable', 'uuid',
                Rule::exists('specializations', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('levels', 'name')
                    ->where('academic_year_id', $level->academic_year_id)
                    ->ignore($level),
            ],
            'code' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('levels', 'code')
                    ->where('academic_year_id', $level->academic_year_id)
                    ->ignore($level),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
