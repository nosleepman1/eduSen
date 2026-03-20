<?php

namespace App\Repositories\Api\V1\Academic;

use App\Models\Grade;
use Illuminate\Support\Collection;

class GradeRepository
{
    /**
     * RG-003, locked to guard the same phantom-read race as every other
     * unique-combination check in this domain.
     */
    public function existsFor(string $evaluationId, string $enrollmentId): bool
    {
        return Grade::query()
            ->where('evaluation_id', $evaluationId)
            ->where('enrollment_id', $enrollmentId)
            ->lockForUpdate()
            ->exists();
    }

    /**
     * Every countable Grade (Graded/Absent) for an enrollment, eager
     * loading the Evaluation's coefficient/teaching_assignment for the
     * subject-average computation.
     */
    public function countableGradesFor(string $enrollmentId, ?string $academicPeriodId = null): Collection
    {
        return Grade::query()
            ->where('enrollment_id', $enrollmentId)
            ->countsTowardAverage()
            ->with('evaluation.teachingAssignment.curriculum')
            ->whereHas('evaluation', fn ($query) => $query->when(
                $academicPeriodId,
                fn ($q) => $q->where('academic_period_id', $academicPeriodId)
            ))
            ->get();
    }

    public function hasPendingGrades(string $enrollmentId, ?string $academicPeriodId = null): bool
    {
        return Grade::query()
            ->where('enrollment_id', $enrollmentId)
            ->where('status', \App\Enums\GradeStatusEnum::Pending)
            ->whereHas('evaluation', fn ($query) => $query->when(
                $academicPeriodId,
                fn ($q) => $q->where('academic_period_id', $academicPeriodId)
            ))
            ->exists();
    }
}
