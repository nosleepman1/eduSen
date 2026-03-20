<?php

namespace App\Http\Requests\Api\V1\School;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonnelRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(['ADMIN', 'TEACHER', 'SUPERVISOR', 'ACCOUNTANT', 'SECRETARY', 'LIBRARIAN'])],
        ];
    }
}
