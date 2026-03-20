<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        $classroom = Classroom::factory()->create(['status' => 'ACTIVE']);
        $student = Student::factory()->create(['school_id' => $classroom->school_id]);

        return [
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'registration_number' => 'ENR-' . fake()->unique()->numerify('######'),
            'enrolled_at' => now(),
            'ended_at' => null,
            'status' => 'ACTIVE',
            'archive_reason' => null,
        ];
    }
}
