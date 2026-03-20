<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    public function definition(): array
    {
        $year = AcademicYear::factory()->create();

        // The semantic prefix stays readable; the unique numeric suffix
        // keeps ->unique() safe against exhaustion across a large test
        // suite while still catching same-year collisions within a
        // single test (two bare creates sharing an explicit academic_year_id).
        return [
            'school_id' => $year->school_id,
            'academic_year_id' => $year->id,
            'name' => fake()->randomElement(['Semestre', 'Trimestre']) . ' ' . fake()->unique()->numberBetween(1, 999999),
            'start_date' => $year->start_date->toDateString(),
            'end_date' => $year->end_date->toDateString(),
            'status' => 'DRAFT',
        ];
    }
}
