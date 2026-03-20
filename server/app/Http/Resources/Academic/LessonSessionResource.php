<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schedule_entry_id' => $this->schedule_entry_id,
            'session_date' => $this->session_date?->toDateString(),
            'scheduled_start_time' => $this->scheduled_start_time,
            'scheduled_end_time' => $this->scheduled_end_time,
            'actual_start' => $this->actual_start,
            'actual_end' => $this->actual_end,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellation_reason,
            'admin_comment' => $this->admin_comment,
            'schedule_entry' => $this->whenLoaded('scheduleEntry', fn () => [
                'id' => $this->scheduleEntry->id,
                'teaching_assignment_id' => $this->scheduleEntry->teaching_assignment_id,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
