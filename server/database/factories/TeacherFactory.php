<?php

namespace Database\Factories;

use App\Models\Personnel;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'personnel_id' => Personnel::factory(),
            'speciality' => fake()->randomElement(['Mathématiques', 'Français', 'SVT', 'Anglais']),
            'diploma' => fake()->randomElement(['Licence', 'Master', 'CAP/CAEM']),
            'license_number' => fake()->optional()->numerify('LIC-#####'),
            'years_experience' => fake()->numberBetween(0, 25),
        ];
    }
}
