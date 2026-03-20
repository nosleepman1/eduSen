<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderLevelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required', 'uuid',
                Rule::exists('academic_years', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => [
                'uuid',
                Rule::exists('levels', 'id')->where('academic_year_id', $this->input('academic_year_id')),
            ],
        ];
    }
}
