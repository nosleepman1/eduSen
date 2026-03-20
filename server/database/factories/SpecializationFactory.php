<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Specialization;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecializationFactory extends Factory
{
    protected $model = Specialization::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->randomElement(['Scientifique S1', 'Littéraire L2', 'Gestion G']),
            'code' => 'SP-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
        ];
    }
}
