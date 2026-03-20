<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        // A wide numeric range (not a 4-item array) keeps ->unique() safe
        // against exhaustion across a large test suite that creates many
        // years — directly, or transitively via Level/AcademicPeriod
        // factories — while still catching same-school collisions within
        // a single test (two bare creates sharing an explicit school_id).
        $startYear = fake()->unique()->numberBetween(2000, 2999);
        $endYear = $startYear + 1;

        return [
            'school_id' => School::factory(),
            'name' => "{$startYear} - {$endYear}",
            'start_date' => Carbon::createFromDate($startYear, 10, 1)->toDateString(),
            'end_date' => Carbon::createFromDate($endYear, 7, 31)->toDateString(),
            'status' => 'DRAFT',
        ];
    }
}
