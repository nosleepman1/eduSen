<?php

namespace App\Http\Requests\Api\V1\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'min:2', 'max:100'],
            'lastname' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', Rule::unique('school_registration_requests', 'email'), Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'regex:/^(\+221|00221)?[0-9]{9}$/'],
            'school_name' => ['required', 'string', 'min:3', 'max:255'],
            'school_email' => ['required', 'email', 'different:email'],
            'school_phone' => ['required', 'string'],
            'ninea' => ['required', 'string', 'regex:/^[0-9]{7}[A-Z]{2}$/', Rule::unique('schools', 'ninea'), Rule::unique('school_registration_requests', 'ninea')],
            'message' => ['nullable', 'string'],
        ];
    }
}
