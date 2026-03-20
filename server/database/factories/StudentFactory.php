<?php

namespace Database\Factories;

use App\Enums\GenderEnum;
use App\Enums\StudentStatusEnum;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'student_number' => 'STD-' . fake()->unique()->numerify('######'),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-5 years'),
            'birth_place' => fake()->city(),
            'gender' => fake()->randomElement([GenderEnum::Male, GenderEnum::Female]),
            'nationality' => 'Sénégalaise',
            'status' => StudentStatusEnum::Active,
            'enrolled_at' => now(),
        ];
    }
}
