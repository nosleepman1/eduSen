<?php

namespace App\Http\Requests\Api\V1\Billing;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'uuid', \Illuminate\Validation\Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'billing_cycle_id' => ['required', 'uuid', \Illuminate\Validation\Rule::exists('billing_cycles', 'id')->where('active', true)],
        ];
    }
}
