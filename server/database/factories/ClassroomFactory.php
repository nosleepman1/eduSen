<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        $level = Level::factory()->create();

        // Letter prefix + unique numeric suffix: keeps ->unique() safe
        // against exhaustion across a large test suite while still catching
        // same-level collisions within a single test (two bare creates
        // sharing an explicit level_id) — RC-004.
        return [
            'school_id' => $level->school_id,
            'academic_year_id' => $level->academic_year_id,
            'level_id' => $level->id,
            'specialization_id' => null,
            'name' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']) . '-' . fake()->unique()->numberBetween(1, 999999),
            'code' => strtoupper(fake()->unique()->bothify('CLS-#####')),
            'capacity' => fake()->numberBetween(20, 50),
            'main_teacher_id' => null,
            'main_room' => null,
            'status' => 'DRAFT',
            'description' => null,
            'end_date_override' => null,
        ];
    }
}
