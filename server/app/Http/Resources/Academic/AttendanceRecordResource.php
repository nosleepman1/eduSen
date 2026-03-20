<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_session_id' => $this->lesson_session_id,
            'enrollment_id' => $this->enrollment_id,
            'status' => $this->status,
            'actual_arrival_time' => $this->actual_arrival_time,
            'absence_reason' => $this->absence_reason,
            'justification' => $this->justification,
            'justification_status' => $this->justification_status,
            'comment' => $this->comment,
            'enrollment' => $this->whenLoaded('enrollment', fn () => [
                'id' => $this->enrollment->id,
                'student_id' => $this->enrollment->student_id,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
