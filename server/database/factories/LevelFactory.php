<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        $year = AcademicYear::factory()->create();

        // Semantic prefix + unique numeric suffix: keeps ->unique() safe
        // against exhaustion across a large test suite while still catching
        // same-year collisions within a single test (two bare creates
        // sharing an explicit academic_year_id) — RL-002/RL-003.
        $name = fake()->randomElement([
            'CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2',
            '6ème', '5ème', '4ème', '3ème', 'Seconde', 'Première', 'Terminale',
        ]) . ' ' . fake()->unique()->numberBetween(1, 999999);

        return [
            'school_id' => $year->school_id,
            'academic_year_id' => $year->id,
            'specialization_id' => null,
            'name' => $name,
            'code' => strtoupper(fake()->unique()->bothify('LVL-#####')),
            'display_order' => fake()->unique()->numberBetween(1, 999999),
            'description' => null,
        ];
    }
}
