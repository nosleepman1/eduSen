<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'academic_period_id' => $this->academic_period_id,
            'teaching_assignment_id' => $this->teaching_assignment_id,
            'lesson_session_id' => $this->lesson_session_id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'evaluation_date' => $this->evaluation_date?->toDateString(),
            'max_score' => $this->max_score,
            'coefficient' => $this->coefficient,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
