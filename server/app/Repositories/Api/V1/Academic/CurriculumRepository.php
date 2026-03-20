<?php

namespace App\Repositories\Api\V1\Academic;

use App\Models\Curriculum;
use Illuminate\Support\Collection;

class CurriculumRepository
{
    /**
     * Must be called inside the same transaction that performs the
     * create/update. Locks every curriculum row for the level (not just
     * rows matching the exact specialization/subject) so a concurrent
     * transaction can't insert a fresh conflicting row while this one is
     * mid-flight — an exact-match WHERE clause locks nothing when the
     * conflicting row doesn't exist yet (phantom read), which is the
     * common case since every subject is new to a level on its first
     * curriculum line. NULL specialization_id is compared correctly
     * (IS NULL) by the PHP equality check below, covering RCU-005 for
     * both the with- and without-specialization cases — no DB unique
     * constraint can express the latter (NULL <> NULL in any index).
     */
    public function existsForCombination(
        string $academicYearId,
        string $levelId,
        ?string $specializationId,
        string $subjectId,
        ?string $excludeId = null
    ): bool {
        return Curriculum::query()
            ->where('academic_year_id', $academicYearId)
            ->where('level_id', $levelId)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->get()
            ->contains(fn (Curriculum $existing) => $existing->specialization_id === $specializationId && $existing->subject_id === $subjectId);
    }

    public function forYear(string $academicYearId): Collection
    {
        return Curriculum::query()
            ->where('academic_year_id', $academicYearId)
            ->with('level')
            ->get();
    }
}
