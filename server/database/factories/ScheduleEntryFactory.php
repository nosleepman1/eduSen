<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Personnel;
use App\Models\ScheduleEntry;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleEntryFactory extends Factory
{
    protected $model = ScheduleEntry::class;

    public function definition(): array
    {
        $classroom = Classroom::factory()->create(['status' => 'ACTIVE']);
        $timetable = Timetable::factory()->create([
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
        ]);
        $curriculum = Curriculum::factory()->create([
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'level_id' => $classroom->level_id,
            'specialization_id' => $classroom->specialization_id,
        ]);
        $personnel = Personnel::factory()->create(['school_id' => $classroom->school_id]);
        $teacher = Teacher::factory()->create(['personnel_id' => $personnel->id]);
        $assignment = TeachingAssignment::factory()->create([
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'curriculum_id' => $curriculum->id,
            'teacher_id' => $teacher->id,
        ]);

        return [
            'school_id' => $classroom->school_id,
            'timetable_id' => $timetable->id,
            'teaching_assignment_id' => $assignment->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'room' => null,
            'frequency' => 'WEEKLY',
            'status' => 'ACTIVE',
        ];
    }
}
