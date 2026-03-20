<?php

namespace App\Http\Requests\Api\V1\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $period = $this->route('academic_period');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('academic_periods', 'name')
                    ->where('academic_year_id', $period?->academic_year_id)
                    ->ignore($period?->id),
            ],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
        ];
    }
}
