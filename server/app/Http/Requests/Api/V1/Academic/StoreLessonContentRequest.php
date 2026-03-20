<?php

namespace App\Http\Requests\Api\V1\Academic;

use App\Support\TenantHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLessonContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lesson_session_id' => [
                'required', 'uuid',
                Rule::exists('lesson_sessions', 'id')->where('school_id', TenantHelper::currentSchoolId()),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'progress_number' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
