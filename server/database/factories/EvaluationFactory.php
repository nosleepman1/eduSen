<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Evaluation;
use App\Models\TeachingAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        $assignment = TeachingAssignment::factory()->create();
        $period = AcademicPeriod::factory()->create([
            'school_id' => $assignment->school_id,
            'academic_year_id' => $assignment->academic_year_id,
        ]);

        return [
            'school_id' => $assignment->school_id,
            'academic_year_id' => $assignment->academic_year_id,
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'lesson_session_id' => null,
            'type' => 'DEVOIR',
            'title' => 'Devoir 1',
            'evaluation_date' => $period->start_date,
            'max_score' => 20,
            'status' => 'DRAFT',
        ];
    }
}
