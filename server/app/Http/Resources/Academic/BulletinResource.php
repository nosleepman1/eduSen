<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulletinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_period_id' => $this->academic_period_id,
            'enrollment_id' => $this->enrollment_id,
            'general_average' => $this->general_average,
            'class_rank' => $this->class_rank,
            'total_students' => $this->total_students,
            'mention' => $this->mention,
            'class_council_comment' => $this->class_council_comment,
            'status' => $this->status,
            'generated_at' => $this->generated_at,
            'published_at' => $this->published_at,
            'lines' => BulletinLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
