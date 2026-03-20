<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Personnel;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeachingAssignmentFactory extends Factory
{
    protected $model = TeachingAssignment::class;

    public function definition(): array
    {
        $classroom = Classroom::factory()->create(['status' => 'ACTIVE']);
        $curriculum = Curriculum::factory()->create([
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'level_id' => $classroom->level_id,
            'specialization_id' => $classroom->specialization_id,
        ]);
        $personnel = Personnel::factory()->create(['school_id' => $classroom->school_id]);
        $teacher = Teacher::factory()->create(['personnel_id' => $personnel->id]);

        return [
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculum->id,
            'teacher_id' => $teacher->id,
            'role' => 'PRINCIPAL',
            'started_at' => now(),
            'ended_at' => null,
            'status' => 'ACTIVE',
        ];
    }
}
