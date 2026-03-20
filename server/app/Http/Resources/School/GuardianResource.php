<?php

namespace App\Http\Resources\School;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'phone_secondary' => $this->phone_secondary,
            'email' => $this->email,
            'profession' => $this->profession,
            'address' => $this->address,
            'national_id' => $this->national_id,
            // Student::guardians() carries relationship/is_primary_contact on
            // the guardian_student pivot; without this, a client that just
            // linked a guardian has no way to read back what it wrote.
            'relationship' => $this->whenPivotLoaded('guardian_student', fn () => $this->pivot->relationship),
            'is_primary_contact' => $this->whenPivotLoaded('guardian_student', fn () => (bool) $this->pivot->is_primary_contact),
        ];
    }
}
