<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'level_id' => $this->level_id,
            'specialization_id' => $this->specialization_id,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'code' => $this->code,
            'capacity' => $this->capacity,
            'main_teacher_id' => $this->main_teacher_id,
            'main_room' => $this->main_room,
            'status' => $this->status,
            'description' => $this->description,
            'end_date_override' => $this->end_date_override?->toDateString(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
