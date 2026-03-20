<?php

namespace App\Http\Resources\School;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'birth_place' => $this->birth_place,
            'gender' => $this->gender?->value,
            'nationality' => $this->nationality,
            'blood_group' => $this->blood_group,
            'medical_info' => $this->medical_info,
            'address' => $this->address,
            'status' => $this->status?->value,
            'enrolled_at' => $this->enrolled_at?->toDateString(),
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
        ];
    }
}
