<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecializationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('specializations', 'code')
                    ->where('school_id', TenantHelper::currentSchoolId())
                    ->ignore($this->route('specialization')),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
