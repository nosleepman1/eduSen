<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'name')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('subjects', 'code')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
