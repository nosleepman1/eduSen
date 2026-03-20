<?php

namespace Database\Factories;

use App\Enums\PersonnelStatusEnum;
use App\Models\Personnel;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonnelFactory extends Factory
{
    protected $model = Personnel::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'user_id' => User::factory(),
            'registration_number' => 'EMP-' . fake()->unique()->numerify('######'),
            'hire_date' => fake()->date(),
            'department' => fake()->randomElement(['Administration', 'Pédagogie']),
            'position' => fake()->randomElement(['Secrétaire', 'Surveillant', 'Enseignant']),
            'status' => PersonnelStatusEnum::Active,
        ];
    }
}
