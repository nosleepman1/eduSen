<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\EvaluationTypeEnum;
use App\Models\Evaluation;

class EvaluationRepository
{
    /**
     * "Créer deux Examens pour la même matière sur la même période" is a
     * non-blocking warning, not a refusal — no locking needed here since
     * nothing is refused on the race, just flagged for the director.
     */
    public function hasAnotherExam(string $teachingAssignmentId, string $academicPeriodId, ?string $excludeId = null): bool
    {
        return Evaluation::query()
            ->where('teaching_assignment_id', $teachingAssignmentId)
            ->where('academic_period_id', $academicPeriodId)
            ->where('type', EvaluationTypeEnum::Examen)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->exists();
    }
}
