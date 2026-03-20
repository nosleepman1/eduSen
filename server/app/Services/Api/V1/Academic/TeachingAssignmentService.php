<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\TeachingAssignmentRoleEnum;
use App\Enums\TeachingAssignmentStatusEnum;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Repositories\Api\V1\Academic\TeachingAssignmentRepository;
use App\Services\Api\V1\Billing\SubscriptionLimitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeachingAssignmentService
{
    public function __construct(
        private readonly TeachingAssignmentRepository $repository,
        private readonly SubscriptionLimitService $subscriptionLimits
    ) {}

    /**
     * "Compatibilité automatique" from the spec: the classroom and the
     * curriculum must agree on academic year, level, and — when the
     * curriculum is track-specific — specialization. A curriculum with no
     * specialization is generic and compatible with any track of its level.
     */
    private function assertCompatible(Classroom $classroom, Curriculum $curriculum): void
    {
        if ($classroom->academic_year_id !== $curriculum->academic_year_id) {
            throw ValidationException::withMessages([
                'curriculum_id' => "Le programme pédagogique n'appartient pas à la même année scolaire que la classe.",
            ]);
        }

        if ($classroom->level_id !== $curriculum->level_id) {
            throw ValidationException::withMessages([
                'curriculum_id' => "Le programme pédagogique n'appartient pas au même niveau que la classe.",
            ]);
        }

        if ($curriculum->specialization_id !== null && $curriculum->specialization_id !== $classroom->specialization_id) {
            throw ValidationException::withMessages([
                'curriculum_id' => "Le programme pédagogique appartient à une autre spécialisation que la classe.",
            ]);
        }
    }

    public function create(array $data, Classroom $classroom, Curriculum $curriculum, Teacher $teacher): TeachingAssignment
    {
        $this->assertCompatible($classroom, $curriculum);

        $role = isset($data['role']) ? TeachingAssignmentRoleEnum::from($data['role']) : TeachingAssignmentRoleEnum::Principal;

        // RTA-011 caps *distinct* assigned teachers, so a teacher who already
        // holds an active assignment never counts against the limit again —
        // only a genuinely new teacher can push the count over it.
        $teacherAlreadyAssigned = $teacher->teachingAssignments()->where('status', TeachingAssignmentStatusEnum::Active)->exists();

        if (! $teacherAlreadyAssigned && $this->subscriptionLimits->hasReachedAssignedTeacherLimit($classroom->school)) {
            throw ValidationException::withMessages([
                'teacher_id' => "Le nombre maximum d'enseignants de votre abonnement est atteint.",
            ]);
        }

        return DB::transaction(function () use ($data, $classroom, $curriculum, $teacher, $role) {
            if ($role === TeachingAssignmentRoleEnum::Principal
                && $this->repository->hasActivePrincipal($classroom->id, $curriculum->id)
            ) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'Cette matière a déjà un titulaire actif dans cette classe.',
                ]);
            }

            return TeachingAssignment::create([
                'school_id' => $classroom->school_id,
                'academic_year_id' => $classroom->academic_year_id,
                'classroom_id' => $classroom->id,
                'curriculum_id' => $curriculum->id,
                'teacher_id' => $teacher->id,
                'role' => $role,
                'started_at' => $data['started_at'] ?? now()->toDateString(),
                'status' => TeachingAssignmentStatusEnum::Active,
            ]);
        });
    }

    public function update(TeachingAssignment $assignment, array $data): TeachingAssignment
    {
        $assignment->update([
            'started_at' => $data['started_at'] ?? $assignment->started_at,
        ]);

        return $assignment;
    }

    /**
     * "Changement d'enseignant": closes the current assignment and opens a
     * new one for the replacement teacher, same classroom/curriculum/role.
     * Past evaluations stay linked to the closed assignment's teacher_id —
     * nothing here touches historical rows.
     */
    public function reassign(TeachingAssignment $assignment, Teacher $newTeacher): TeachingAssignment
    {
        if (! $assignment->isActive()) {
            throw ValidationException::withMessages([
                'assignment' => 'Seule une affectation active peut être réaffectée.',
            ]);
        }

        return DB::transaction(function () use ($assignment, $newTeacher) {
            $assignment->update([
                'status' => TeachingAssignmentStatusEnum::Closed,
                'ended_at' => now()->toDateString(),
            ]);

            if ($assignment->role === TeachingAssignmentRoleEnum::Principal
                && $this->repository->hasActivePrincipal($assignment->classroom_id, $assignment->curriculum_id)
            ) {
                throw ValidationException::withMessages([
                    'classroom_id' => 'Cette matière a déjà un titulaire actif dans cette classe.',
                ]);
            }

            return TeachingAssignment::create([
                'school_id' => $assignment->school_id,
                'academic_year_id' => $assignment->academic_year_id,
                'classroom_id' => $assignment->classroom_id,
                'curriculum_id' => $assignment->curriculum_id,
                'teacher_id' => $newTeacher->id,
                'role' => $assignment->role,
                'started_at' => now()->toDateString(),
                'status' => TeachingAssignmentStatusEnum::Active,
            ]);
        });
    }

    public function close(TeachingAssignment $assignment): TeachingAssignment
    {
        if (! $assignment->isActive()) {
            throw ValidationException::withMessages([
                'assignment' => 'Cette affectation est déjà clôturée.',
            ]);
        }

        $assignment->update([
            'status' => TeachingAssignmentStatusEnum::Closed,
            'ended_at' => now()->toDateString(),
        ]);

        return $assignment;
    }

    /**
     * Soft-delete only, same as Classroom/Curriculum/Enrollment — an
     * assignment referenced by evaluations is never hard-deleted (spec's
     * "cas limite": archiving is always allowed, deleting never is).
     */
    public function archive(TeachingAssignment $assignment): void
    {
        $assignment->delete();
    }

    public function restore(TeachingAssignment $assignment): TeachingAssignment
    {
        $assignment->restore();

        return $assignment;
    }

    /**
     * Weekly workload: sum of weekly_hours across every Curriculum the
     * teacher currently holds an active assignment for. Computed on demand,
     * nothing is persisted (spec: "Aucune donnée n'est stockée").
     */
    public function workloadFor(Teacher $teacher): int
    {
        return $this->repository->workloadFor($teacher->id);
    }
}
