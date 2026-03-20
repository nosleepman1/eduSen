<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimetableFactory extends Factory
{
    protected $model = Timetable::class;

    public function definition(): array
    {
        $classroom = Classroom::factory()->create();

        return [
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'name' => fake()->randomElement(['Emploi du temps général', 'Emploi du temps provisoire']),
            'description' => null,
            'status' => 'DRAFT',
        ];
    }
}
