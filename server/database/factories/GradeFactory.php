<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        $evaluation = Evaluation::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'school_id' => $evaluation->school_id,
            'classroom_id' => $evaluation->teachingAssignment->classroom_id,
        ]);

        return [
            'school_id' => $evaluation->school_id,
            'evaluation_id' => $evaluation->id,
            'enrollment_id' => $enrollment->id,
            'value' => null,
            'status' => 'PENDING',
        ];
    }
}
