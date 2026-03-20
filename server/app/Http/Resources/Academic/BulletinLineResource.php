<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulletinLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'curriculum_id' => $this->curriculum_id,
            'subject_average' => $this->subject_average,
            'coefficient' => $this->coefficient,
            'points' => $this->points,
            'teacher_comment' => $this->teacher_comment,
        ];
    }
}
