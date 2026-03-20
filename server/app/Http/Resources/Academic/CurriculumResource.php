<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurriculumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'level_id' => $this->level_id,
            'specialization_id' => $this->specialization_id,
            'subject_id' => $this->subject_id,
            'coefficient' => $this->coefficient,
            'weekly_hours' => $this->weekly_hours,
            'display_order' => $this->display_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
