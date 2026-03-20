<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('subjects', 'name')
                    ->where('school_id', TenantHelper::currentSchoolId())
                    ->ignore($this->route('subject')),
            ],
            'code' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('subjects', 'code')
                    ->where('school_id', TenantHelper::currentSchoolId())
                    ->ignore($this->route('subject')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
