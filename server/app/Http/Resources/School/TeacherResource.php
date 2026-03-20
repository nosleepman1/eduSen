<?php

namespace App\Http\Resources\School;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'personnel_id' => $this->personnel_id,
            'full_name' => $this->full_name,
            'speciality' => $this->speciality,
            'diploma' => $this->diploma,
            'license_number' => $this->license_number,
            'years_experience' => $this->years_experience,
        ];
    }
}
