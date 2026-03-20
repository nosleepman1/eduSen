<?php

namespace App\Http\Requests\Api\V1\School;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['sometimes', 'required', 'string', 'max:255'],
            'lastname' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('guardians', 'phone')
                    ->where('school_id', TenantHelper::currentSchoolId())
                    ->ignore($this->route('guardian')),
            ],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('guardians', 'email')
                    ->where('school_id', TenantHelper::currentSchoolId())
                    ->ignore($this->route('guardian')),
            ],
            'profession' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
        ];
    }
}
