<?php

namespace Database\Factories;

use App\Models\LessonSession;
use App\Models\ScheduleEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonSessionFactory extends Factory
{
    protected $model = LessonSession::class;

    public function definition(): array
    {
        $entry = ScheduleEntry::factory()->create();

        return [
            'school_id' => $entry->school_id,
            'schedule_entry_id' => $entry->id,
            'session_date' => now()->addWeek()->toDateString(),
            'scheduled_start_time' => $entry->start_time,
            'scheduled_end_time' => $entry->end_time,
            'status' => 'SCHEDULED',
        ];
    }
}
