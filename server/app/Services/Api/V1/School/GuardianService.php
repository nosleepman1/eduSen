<?php

namespace App\Services\Api\V1\School;

use App\Models\Guardian;

class GuardianService
{
    public function create(array $data, string $schoolId): Guardian
    {
        return Guardian::create([
            'school_id' => $schoolId,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'phone' => $data['phone'],
            'phone_secondary' => $data['phone_secondary'] ?? null,
            'email' => $data['email'] ?? null,
            'profession' => $data['profession'] ?? null,
            'address' => $data['address'] ?? null,
            'national_id' => $data['national_id'] ?? null,
        ]);
    }

    public function update(Guardian $guardian, array $data): Guardian
    {
        $guardian->update(array_intersect_key($data, array_flip([
            'firstname', 'lastname', 'phone', 'phone_secondary', 'email', 'profession', 'address', 'national_id',
        ])));

        return $guardian->fresh();
    }

    public function archive(Guardian $guardian): void
    {
        $guardian->delete();
    }

    public function restore(Guardian $guardian): Guardian
    {
        $guardian->restore();

        return $guardian->fresh();
    }
}
