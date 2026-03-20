<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignPrincipalTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'main_teacher_id' => [
                'nullable', 'uuid',
                Rule::exists('personnel', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
        ];
    }
}
