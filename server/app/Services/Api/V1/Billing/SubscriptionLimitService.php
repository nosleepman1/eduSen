<?php

namespace App\Services\Api\V1\Billing;

use App\Models\School;
use App\Repositories\Api\V1\Academic\EnrollmentRepository;
use App\Repositories\Api\V1\Academic\TeachingAssignmentRepository;
use App\Repositories\Api\V1\School\StudentRepository;
use App\Repositories\Api\V1\School\TeacherRepository;

class SubscriptionLimitService
{
    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly TeacherRepository $teacherRepository,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly TeachingAssignmentRepository $teachingAssignmentRepository
    ) {}

    public function hasReachedStudentLimit(School $school): bool
    {
        $maxStudents = $school->activeSubscription?->plan?->max_students;

        if ($maxStudents === null) {
            return true;
        }

        return $this->studentRepository->countActive($school->id) >= $maxStudents;
    }

    public function hasReachedTeacherLimit(School $school): bool
    {
        $maxTeachers = $school->activeSubscription?->plan?->max_teachers;

        if ($maxTeachers === null) {
            return true;
        }

        return $this->teacherRepository->countActive($school->id) >= $maxTeachers;
    }

    /**
     * RE-011: active enrollments for the given academic year are capped by
     * the same max_students ceiling as the school's raw headcount — a school
     * could otherwise re-enroll the same student pool across classes past
     * what its plan allows for a single year.
     */
    public function hasReachedEnrollmentLimit(School $school, string $academicYearId): bool
    {
        $maxStudents = $school->activeSubscription?->plan?->max_students;

        if ($maxStudents === null) {
            return true;
        }

        return $this->enrollmentRepository->countActiveInYear($academicYearId) >= $maxStudents;
    }

    /**
     * RTA-011: distinct teachers holding at least one active
     * TeachingAssignment — deliberately different from
     * hasReachedTeacherLimit(), which counts the school's raw active-Teacher
     * headcount regardless of whether they're actually assigned to anything.
     */
    public function hasReachedAssignedTeacherLimit(School $school): bool
    {
        $maxTeachers = $school->activeSubscription?->plan?->max_teachers;

        if ($maxTeachers === null) {
            return true;
        }

        return $this->teachingAssignmentRepository->countDistinctActiveTeachers($school->id) >= $maxTeachers;
    }
}
