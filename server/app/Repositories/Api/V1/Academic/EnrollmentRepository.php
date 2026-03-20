<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\EnrollmentStatusEnum;
use App\Models\Enrollment;

class EnrollmentRepository
{
    /**
     * RE-004: a student can only hold one Active enrollment per academic
     * year. Locks the candidate rows so a concurrent request can't create a
     * second active enrollment for the same student/year between this check
     * and the insert — same phantom-read guard used by CurriculumRepository.
     */
    public function hasActiveEnrollmentInYear(string $studentId, string $academicYearId, ?string $excludeId = null): bool
    {
        return Enrollment::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', EnrollmentStatusEnum::Active)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->exists();
    }

    /**
     * Counts via ->get()->count() rather than ->count() — Postgres rejects
     * FOR UPDATE combined with an aggregate function in the same query
     * ("FOR UPDATE n'est pas autorisé avec les fonctions d'agrégat"), which
     * SQLite (the test suite's driver) never enforces. Fetching the locked
     * rows and counting in PHP avoids the aggregate entirely.
     */
    public function countActiveInClassroom(string $classroomId): int
    {
        return Enrollment::query()
            ->where('classroom_id', $classroomId)
            ->where('status', EnrollmentStatusEnum::Active)
            ->lockForUpdate()
            ->get()
            ->count();
    }

    public function countActiveInYear(string $academicYearId): int
    {
        return Enrollment::query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', EnrollmentStatusEnum::Active)
            ->count();
    }
}
