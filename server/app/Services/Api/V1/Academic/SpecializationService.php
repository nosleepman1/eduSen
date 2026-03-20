<?php

namespace App\Services\Api\V1\Academic;

use App\Models\Specialization;
use Illuminate\Support\Facades\DB;

class SpecializationService
{
    public function create(array $data, string $schoolId): Specialization
    {
        return DB::transaction(function () use ($data, $schoolId) {
            return Specialization::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
            ]);
        });
    }

    public function update(Specialization $specialization, array $data): Specialization
    {
        $specialization->update($data);

        return $specialization->fresh();
    }

    public function archive(Specialization $specialization): void
    {
        $specialization->delete();
    }

    public function restore(Specialization $specialization): Specialization
    {
        $specialization->restore();

        return $specialization->fresh();
    }
}
