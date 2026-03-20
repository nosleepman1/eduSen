<?php

namespace App\Http\Requests\Api\V1\School;

use App\Enums\PhoneTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSchoolDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address' => ['required', 'array'],
            'address.country' => ['nullable', 'string'],
            'address.region' => ['required', 'string'],
            'address.department' => ['nullable', 'string'],
            'address.city' => ['required', 'string'],
            'address.commune' => ['nullable', 'string'],
            'address.street' => ['nullable', 'string'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'phones' => ['required', 'array', 'min:1'],
            'phones.*.phone_number' => ['required', 'string'],
            'phones.*.type' => ['required', Rule::enum(PhoneTypeEnum::class)],
            'phones.*.country_code' => ['nullable', 'string'],
            'phones.*.dial_code' => ['nullable', 'string'],
            'phones.*.is_primary' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $primaryCount = collect($this->input('phones', []))->where('is_primary', true)->count();

                if ($primaryCount !== 1) {
                    $validator->errors()->add('phones', 'Exactement un telephone doit etre marque comme principal.');
                }
            },
        ];
    }
}
