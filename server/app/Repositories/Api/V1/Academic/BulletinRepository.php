<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\EvaluationStatusEnum;
use App\Models\Bulletin;
use App\Models\Evaluation;

class BulletinRepository
{
    public function existsFor(string $academicPeriodId, string $enrollmentId): bool
    {
        return Bulletin::query()
            ->where('academic_period_id', $academicPeriodId)
            ->where('enrollment_id', $enrollmentId)
            ->lockForUpdate()
            ->exists();
    }

    /**
     * RB-004: every Evaluation of the classroom's TeachingAssignments for
     * this period must be Closed or Locked — an Open or Draft evaluation
     * anywhere in the classroom blocks generation for every student in it.
     */
    public function hasUnclosedEvaluations(string $classroomId, string $academicPeriodId): bool
    {
        return Evaluation::query()
            ->where('academic_period_id', $academicPeriodId)
            ->whereHas('teachingAssignment', fn ($query) => $query->where('classroom_id', $classroomId))
            ->whereNotIn('status', [EvaluationStatusEnum::Closed, EvaluationStatusEnum::Locked])
            ->exists();
    }
}
