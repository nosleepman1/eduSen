<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_academic_year_id' => [
                'required', 'uuid', 'different:to_academic_year_id',
                Rule::exists('academic_years', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'to_academic_year_id' => [
                'required', 'uuid',
                Rule::exists('academic_years', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
        ];
    }
}
