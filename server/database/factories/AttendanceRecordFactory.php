<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\LessonSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        $session = LessonSession::factory()->create();
        $enrollment = Enrollment::factory()->create(['school_id' => $session->school_id]);

        return [
            'school_id' => $session->school_id,
            'lesson_session_id' => $session->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'ABSENT',
        ];
    }
}
