<?php

namespace App\Http\Requests\Api\V1\School;

use App\Enums\SchoolEnum;
use App\Enums\SectorEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolBasicInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'file', 'image', 'max:2048', 'mimes:jpg,jpeg,png,svg'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
            'school_type' => ['required', Rule::enum(SchoolEnum::class)],
            'sector' => ['required', Rule::enum(SectorEnum::class)],
            'registration_number' => ['nullable', 'string'],
            'founded_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
