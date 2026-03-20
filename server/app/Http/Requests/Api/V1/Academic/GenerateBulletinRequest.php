<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => [
                'required', 'uuid',
                Rule::exists('enrollments', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'academic_period_id' => [
                'required', 'uuid',
                Rule::exists('academic_periods', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
        ];
    }
}
