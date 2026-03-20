<?php

namespace App\Services\Api\V1\School;

use App\Enums\RoleEnum;
use App\Models\Personnel;
use App\Models\Teacher;
use App\Models\UserRole;
use App\Services\Api\V1\Billing\SubscriptionLimitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherService
{
    public function __construct(
        private SubscriptionLimitService $subscriptionLimits,
        private PersonnelService $personnelService
    ) {}

    public function create(Personnel $personnel, array $data): Teacher
    {
        $hasTeacherRole = UserRole::query()
            ->where('user_id', $personnel->user_id)
            ->where('school_id', $personnel->school_id)
            ->where('role', RoleEnum::Teacher)
            ->where('is_active', true)
            ->exists();

        if (! $hasTeacherRole) {
            throw ValidationException::withMessages([
                'personnel' => "Ce membre du personnel doit d'abord posséder le rôle TEACHER.",
            ]);
        }

        if ($this->subscriptionLimits->hasReachedTeacherLimit($personnel->school)) {
            throw ValidationException::withMessages([
                'teacher' => "Le nombre maximum d'enseignants de votre abonnement est atteint.",
            ]);
        }

        return DB::transaction(function () use ($personnel, $data) {
            return Teacher::create([
                'personnel_id' => $personnel->id,
                'speciality' => $data['speciality'] ?? null,
                'diploma' => $data['diploma'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'years_experience' => $data['years_experience'] ?? null,
            ]);
        });
    }

    public function update(Teacher $teacher, array $data): Teacher
    {
        $teacher->update(array_intersect_key($data, array_flip([
            'speciality', 'diploma', 'license_number', 'years_experience',
        ])));

        return $teacher->fresh();
    }

    public function archive(Teacher $teacher): void
    {
        $personnel = $teacher->personnel;

        if ($personnel) {
            $this->personnelService->archive($personnel);
        }
    }

    public function restore(Teacher $teacher): Teacher
    {
        $personnel = $teacher->personnel()->withTrashed()->first();

        if ($personnel) {
            $this->personnelService->restore($personnel);
        }

        return $teacher->fresh();
    }
}
