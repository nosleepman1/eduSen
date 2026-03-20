<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->company(),
            'code' => 'SCH-' . now()->year . '-' . fake()->unique()->numerify('####'),
            'ninea' => fake()->numerify('#########'),
            'email' => fake()->unique()->companyEmail(),
            'is_active' => true,
            'is_school_setup' => true,
            'setup_step' => 3,
            'setup_completed_at' => now(),
        ];
    }
}
