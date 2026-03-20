<?php

namespace App\Http\Requests\Api\V1\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateLessonSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['COMPLETED', 'CANCELLED', 'POSTPONED'])],
            'reason' => ['required_if:decision,CANCELLED', 'nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
