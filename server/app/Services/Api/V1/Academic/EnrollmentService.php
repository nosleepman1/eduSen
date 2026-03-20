<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\AcademicYearStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Repositories\Api\V1\Academic\EnrollmentRepository;
use App\Services\Api\V1\Billing\SubscriptionLimitService;
use App\Services\Api\V1\School\RegistrationNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepository $repository,
        private readonly RegistrationNumberService $registrationNumbers,
        private readonly SubscriptionLimitService $subscriptionLimits
    ) {}

    /**
     * RE-006/RE-007/RE-005/RE-004/RE-011, in that order — cheapest,
     * no-lock checks first so an obviously-invalid request never reaches the
     * row-locking capacity/uniqueness checks or opens a transaction.
     */
    public function create(array $data, Classroom $classroom, Student $student): Enrollment
    {
        $classroom->loadMissing('academicYear');
        $academicYear = $classroom->academicYear;

        if (! $classroom->isActive()) {
            throw ValidationException::withMessages([
                'classroom_id' => "Cette classe n'est pas active, elle ne peut pas accueillir de nouvelle inscription.",
            ]);
        }

        if ($academicYear->status !== AcademicYearStatusEnum::Active) {
            throw ValidationException::withMessages([
                'classroom_id' => "L'année scolaire de cette classe n'est pas active.",
            ]);
        }

        if ($this->subscriptionLimits->hasReachedEnrollmentLimit($classroom->school, $academicYear->id)) {
            throw ValidationException::withMessages([
                'student_id' => "Le nombre maximum d'élèves de votre abonnement est atteint pour cette année scolaire.",
            ]);
        }

        return DB::transaction(function () use ($data, $classroom, $student, $academicYear) {
            if ($this->repository->countActiveInClassroom($classroom->id) >= $classroom->capacity) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'Cette classe a atteint sa capacité maximale.',
                ]);
            }

            if ($this->repository->hasActiveEnrollmentInYear($student->id, $academicYear->id)) {
                throw ValidationException::withMessages([
                    'student_id' => 'Cet élève est déjà inscrit dans une classe pour cette année scolaire.',
                ]);
            }

            $registrationNumber = $this->registrationNumbers->generate($classroom->school_id, 'ENROLLMENT');

            return Enrollment::create([
                'school_id' => $classroom->school_id,
                'academic_year_id' => $academicYear->id,
                'classroom_id' => $classroom->id,
                'student_id' => $student->id,
                'registration_number' => $registrationNumber,
                'enrolled_at' => $data['enrolled_at'] ?? now()->toDateString(),
                'status' => EnrollmentStatusEnum::Active,
            ]);
        });
    }

    /**
     * Only the enrollment date is mutable here — classroom/student/year
     * changes go through transfer(), which is the only path allowed to move
     * an enrollment between classes (RE-009).
     */
    public function update(Enrollment $enrollment, array $data): Enrollment
    {
        $enrollment->update([
            'enrolled_at' => $data['enrolled_at'] ?? $enrollment->enrolled_at,
        ]);

        return $enrollment;
    }

    /**
     * RE-009: closes the source enrollment and opens a new one in the
     * destination classroom inside a single transaction. Attendance/grade
     * history stays attached to the original (now Transferred) enrollment —
     * nothing here touches those rows.
     */
    public function transfer(Enrollment $enrollment, Classroom $destination): Enrollment
    {
        if ($enrollment->classroom_id === $destination->id) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Impossible de transférer un élève vers sa classe actuelle.',
            ]);
        }

        if (! $enrollment->isActive()) {
            throw ValidationException::withMessages([
                'enrollment' => 'Seule une inscription active peut être transférée.',
            ]);
        }

        $destination->loadMissing('academicYear');

        if (! $destination->isActive()) {
            throw ValidationException::withMessages([
                'classroom_id' => "La classe de destination n'est pas active.",
            ]);
        }

        if ($destination->academic_year_id !== $enrollment->academic_year_id) {
            throw ValidationException::withMessages([
                'classroom_id' => 'La classe de destination doit appartenir à la même année scolaire.',
            ]);
        }

        return DB::transaction(function () use ($enrollment, $destination) {
            if ($this->repository->countActiveInClassroom($destination->id) >= $destination->capacity) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'La classe de destination a atteint sa capacité maximale.',
                ]);
            }

            $enrollment->update([
                'status' => EnrollmentStatusEnum::Transferred,
                'ended_at' => now()->toDateString(),
            ]);

            $registrationNumber = $this->registrationNumbers->generate($enrollment->school_id, 'ENROLLMENT');

            return Enrollment::create([
                'school_id' => $enrollment->school_id,
                'academic_year_id' => $enrollment->academic_year_id,
                'classroom_id' => $destination->id,
                'student_id' => $enrollment->student_id,
                'registration_number' => $registrationNumber,
                'enrolled_at' => now()->toDateString(),
                'status' => EnrollmentStatusEnum::Active,
            ]);
        });
    }

    public function withdraw(Enrollment $enrollment, ?string $reason = null): Enrollment
    {
        if (! $enrollment->isActive()) {
            throw ValidationException::withMessages([
                'enrollment' => 'Seule une inscription active peut être clôturée.',
            ]);
        }

        $enrollment->update([
            'status' => EnrollmentStatusEnum::Withdrawn,
            'ended_at' => now()->toDateString(),
            'archive_reason' => $reason,
        ]);

        return $enrollment;
    }

    /**
     * Archiving is a soft-delete, same as Classroom/Curriculum — it does not
     * overwrite the Active/Transferred/Withdrawn status the enrollment had,
     * so restore() brings back its real prior state, not a synthetic one.
     */
    public function archive(Enrollment $enrollment, ?string $reason = null): void
    {
        if ($reason !== null) {
            $enrollment->update(['archive_reason' => $reason]);
        }

        $enrollment->delete();
    }

    public function restore(Enrollment $enrollment): Enrollment
    {
        $enrollment->restore();

        return $enrollment;
    }
}
