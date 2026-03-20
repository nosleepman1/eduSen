<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        // Semantic prefix + unique numeric suffix: keeps ->unique() safe
        // against exhaustion across a large test suite while still catching
        // same-school collisions within a single test (two bare creates
        // sharing an explicit school_id) — RS-002/RS-003.
        return [
            'school_id' => School::factory(),
            'name' => fake()->randomElement([
                'Mathématiques', 'Français', 'Anglais', 'Physique', 'Chimie',
                'SVT', 'Histoire-Géographie', 'Philosophie', 'Informatique',
            ]) . ' ' . fake()->unique()->numberBetween(1, 999999),
            'code' => strtoupper(fake()->unique()->bothify('SUB-#####')),
            'description' => fake()->sentence(),
        ];
    }
}
