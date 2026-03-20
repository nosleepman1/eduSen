<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'phone' => fake()->unique()->numerify('7#######'),
            'email' => fake()->optional()->safeEmail(),
            'profession' => fake()->jobTitle(),
            'address' => fake()->address(),
            'national_id' => fake()->optional()->numerify('##########'),
        ];
    }
}
