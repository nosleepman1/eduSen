<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\TeachingAssignmentRoleEnum;
use App\Enums\TeachingAssignmentStatusEnum;
use App\Models\TeachingAssignment;

class TeachingAssignmentRepository
{
    /**
     * RTA-009: a subject can only have one active Principal per classroom.
     * Locks the candidate rows so a concurrent request can't create a second
     * one between this check and the insert — same phantom-read guard used
     * by CurriculumRepository::existsForCombination and
     * EnrollmentRepository::hasActiveEnrollmentInYear.
     */
    public function hasActivePrincipal(string $classroomId, string $curriculumId, ?string $excludeId = null): bool
    {
        return TeachingAssignment::query()
            ->where('classroom_id', $classroomId)
            ->where('curriculum_id', $curriculumId)
            ->where('role', TeachingAssignmentRoleEnum::Principal)
            ->where('status', TeachingAssignmentStatusEnum::Active)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->exists();
    }

    /**
     * RTA-011: distinct teachers currently holding at least one active
     * assignment in the school — not the school's raw active-Teacher
     * headcount (that's SubscriptionLimitService::hasReachedTeacherLimit).
     */
    public function countDistinctActiveTeachers(string $schoolId): int
    {
        return TeachingAssignment::query()
            ->where('school_id', $schoolId)
            ->where('status', TeachingAssignmentStatusEnum::Active)
            ->distinct('teacher_id')
            ->count('teacher_id');
    }

    public function workloadFor(string $teacherId): int
    {
        return (int) TeachingAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('status', TeachingAssignmentStatusEnum::Active)
            ->join('curriculums', 'curriculums.id', '=', 'teaching_assignments.curriculum_id')
            ->sum('curriculums.weekly_hours');
    }
}
