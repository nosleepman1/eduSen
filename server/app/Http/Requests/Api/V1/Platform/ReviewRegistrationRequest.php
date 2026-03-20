<?php

namespace App\Http\Requests\Api\V1\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['nullable', 'uuid', Rule::exists('subscription_plans', 'id')],
            'rejection_reason' => ['required_if:action,reject', 'string', 'min:10'],
        ];
    }
}
