<?php

namespace App\Http\Requests\Api\V1\School;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guardian_id' => [
                'required', 'uuid',
                Rule::exists('guardians', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'relationship' => ['required', Rule::in(['FATHER', 'MOTHER', 'GUARDIAN', 'TUTOR', 'OTHER'])],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
