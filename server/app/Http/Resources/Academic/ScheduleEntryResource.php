<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'timetable_id' => $this->timetable_id,
            'teaching_assignment_id' => $this->teaching_assignment_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'room' => $this->room,
            'frequency' => $this->frequency,
            'status' => $this->status,
            'teaching_assignment' => $this->whenLoaded('teachingAssignment', fn () => [
                'id' => $this->teachingAssignment->id,
                'teacher_id' => $this->teachingAssignment->teacher_id,
                'curriculum_id' => $this->teachingAssignment->curriculum_id,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
