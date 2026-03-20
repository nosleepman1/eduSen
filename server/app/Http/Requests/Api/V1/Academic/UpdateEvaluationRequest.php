<?php

namespace App\Http\Requests\Api\V1\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'evaluation_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'numeric', 'min:0.01'],
            'coefficient' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
