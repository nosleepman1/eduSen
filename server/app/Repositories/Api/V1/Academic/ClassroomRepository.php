<?php

namespace App\Repositories\Api\V1\Academic;

use App\Models\Classroom;

class ClassroomRepository
{
    /**
     * Must be called inside the same transaction that performs the
     * create/update. Locks every classroom row for the level (not just
     * rows matching the exact name/specialization) so a concurrent
     * transaction can't insert a fresh conflicting row while this one is
     * mid-flight — an exact-match WHERE clause locks nothing when the
     * conflicting row doesn't exist yet (phantom read), which is the
     * common case since every classroom name is new on its first insert.
     * NULL specialization_id is compared correctly (IS NULL) by the PHP
     * equality check below, covering RC-004 for both the with- and
     * without-specialization cases — no DB unique constraint can express
     * the latter (NULL <> NULL in any index).
     */
    public function existsForCombination(
        string $academicYearId,
        string $levelId,
        ?string $specializationId,
        string $name,
        ?string $excludeId = null
    ): bool {
        return Classroom::query()
            ->where('academic_year_id', $academicYearId)
            ->where('level_id', $levelId)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->get()
            ->contains(fn (Classroom $existing) => $existing->specialization_id === $specializationId && $existing->name === $name);
    }
}
