<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateClassBulletinsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => [
                'required', 'uuid',
                Rule::exists('classrooms', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'academic_period_id' => [
                'required', 'uuid',
                Rule::exists('academic_periods', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
        ];
    }
}
