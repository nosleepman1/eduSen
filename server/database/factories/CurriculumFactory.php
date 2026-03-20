<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\Level;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurriculumFactory extends Factory
{
    protected $model = Curriculum::class;

    public function definition(): array
    {
        $level = Level::factory()->create();

        return [
            'school_id' => $level->school_id,
            'academic_year_id' => $level->academic_year_id,
            'level_id' => $level->id,
            'specialization_id' => null,
            'subject_id' => Subject::factory()->create(['school_id' => $level->school_id])->id,
            'coefficient' => fake()->randomElement([1, 2, 3, 4, 5, 6]),
            'weekly_hours' => fake()->numberBetween(1, 8),
            'display_order' => null,
        ];
    }
}
