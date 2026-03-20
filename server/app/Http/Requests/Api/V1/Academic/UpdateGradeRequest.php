<?php

namespace App\Http\Requests\Api\V1\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['GRADED', 'ABSENT', 'DISPENSED'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
