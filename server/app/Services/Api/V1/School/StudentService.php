<?php

namespace App\Services\Api\V1\School;

use App\Enums\StudentStatusEnum;
use App\Models\School;
use App\Models\Student;
use App\Services\Api\V1\Billing\SubscriptionLimitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function __construct(
        private RegistrationNumberService $registrationNumbers,
        private SubscriptionLimitService $subscriptionLimits
    ) {}

    public function create(array $data, School $school): Student
    {
        if ($this->subscriptionLimits->hasReachedStudentLimit($school)) {
            throw ValidationException::withMessages([
                'student' => "Le nombre maximum d'élèves de votre abonnement est atteint.",
            ]);
        }

        $schoolId = $school->id;

        return DB::transaction(function () use ($data, $schoolId) {
            // Generate unique student number
            $studentNumber = $this->registrationNumbers->generate($schoolId, 'STUDENT');

            $student = Student::create([
                'school_id' => $schoolId,
                'student_number' => $studentNumber,
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'birth_date' => $data['birth_date'] ?? null,
                'birth_place' => $data['birth_place'] ?? null,
                'gender' => $data['gender'] ?? null,
                'nationality' => $data['nationality'] ?? 'Sénégalaise',
                'blood_group' => $data['blood_group'] ?? null,
                'medical_info' => $data['medical_info'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => StudentStatusEnum::Active,
                'enrolled_at' => now(),
            ]);

            return $student;
        });
    }

    public function update(Student $student, array $data): Student
    {
        $student->update(array_intersect_key($data, array_flip([
            'firstname', 'lastname', 'birth_date', 'birth_place', 'gender',
            'nationality', 'blood_group', 'medical_info', 'address',
        ])));

        return $student->fresh();
    }

    public function archive(Student $student): void
    {
        $student->delete();
    }

    public function restore(Student $student): Student
    {
        $student->restore();

        return $student->fresh();
    }

    public function linkGuardian(Student $student, string $guardianId, string $relationship, bool $isPrimary = false): void
    {
        // If this is set as primary, unset other primary contacts for this student
        if ($isPrimary) {
            $student->guardians()->updateExistingPivot(
                $student->guardians->pluck('id'),
                ['is_primary_contact' => false]
            );
        }

        $student->guardians()->syncWithoutDetaching([
            $guardianId => [
                'relationship' => $relationship,
                'is_primary_contact' => $isPrimary,
                'id' => (string) \Illuminate\Support\Str::uuid(),
            ],
        ]);
    }
}
