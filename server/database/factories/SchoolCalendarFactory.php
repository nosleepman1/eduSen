<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolCalendarFactory extends Factory
{
    protected $model = SchoolCalendar::class;

    public function definition(): array
    {
        $year = AcademicYear::factory()->create();

        return [
            'school_id' => $year->school_id,
            'academic_year_id' => $year->id,
            'name' => "Calendrier Officiel {$year->name}",
        ];
    }
}
