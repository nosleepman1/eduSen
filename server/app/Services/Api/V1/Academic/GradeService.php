<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\GradeStatusEnum;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Repositories\Api\V1\Academic\GradeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradeService
{
    public function __construct(
        private readonly GradeRepository $repository
    ) {}

    private function assertCompatible(Evaluation $evaluation, Enrollment $enrollment): void
    {
        $evaluation->loadMissing('teachingAssignment');

        if ($enrollment->classroom_id !== $evaluation->teachingAssignment->classroom_id) {
            throw ValidationException::withMessages([
                'enrollment_id' => "Cet élève n'est pas inscrit dans la classe de cette évaluation (RG-007).",
            ]);
        }
    }

    private function assertMutable(Grade $grade): void
    {
        $grade->loadMissing('evaluation');

        if ($grade->evaluation->isLocked()) {
            throw ValidationException::withMessages([
                'grade' => 'Cette évaluation est verrouillée, ses notes ne peuvent plus être modifiées (RG-005).',
            ]);
        }

        if ($grade->evaluation->trashed()) {
            throw ValidationException::withMessages([
                'grade' => 'Cette évaluation est archivée, ses notes ne peuvent plus être modifiées (RG-006).',
            ]);
        }
    }

    /**
     * "Ouverture de l'Evaluation -> Génération automatique des Grades
     * (Pending)": one Grade per Active enrollment of the classroom the
     * evaluation's TeachingAssignment belongs to. Idempotent — existing
     * Grades are never recreated, so calling this again after a manual
     * addition is safe.
     */
    public function bulkGenerateFor(Evaluation $evaluation): array
    {
        $evaluation->loadMissing('teachingAssignment');
        $classroomId = $evaluation->teachingAssignment->classroom_id;

        return DB::transaction(function () use ($evaluation, $classroomId) {
            $enrollments = Enrollment::query()
                ->where('classroom_id', $classroomId)
                ->where('status', \App\Enums\EnrollmentStatusEnum::Active)
                ->get();

            $created = [];

            foreach ($enrollments as $enrollment) {
                if ($this->repository->existsFor($evaluation->id, $enrollment->id)) {
                    continue;
                }

                $created[] = Grade::create([
                    'school_id' => $evaluation->school_id,
                    'evaluation_id' => $evaluation->id,
                    'enrollment_id' => $enrollment->id,
                    'value' => null,
                    'status' => GradeStatusEnum::Pending,
                ]);
            }

            return $created;
        });
    }

    private function assertValueConsistent(string $status, ?float $value, Evaluation $evaluation): void
    {
        if ($status === GradeStatusEnum::Graded->value) {
            if ($value === null) {
                throw ValidationException::withMessages(['value' => 'La note est obligatoire pour un statut Noté.']);
            }

            if ($value < 0 || $value > (float) $evaluation->max_score) {
                throw ValidationException::withMessages([
                    'value' => "La note doit être comprise entre 0 et {$evaluation->max_score} (RG-004).",
                ]);
            }
        }
    }

    public function create(array $data, Evaluation $evaluation, Enrollment $enrollment): Grade
    {
        $this->assertCompatible($evaluation, $enrollment);

        $status = $data['status'] ?? GradeStatusEnum::Pending->value;
        $this->assertValueConsistent($status, isset($data['value']) ? (float) $data['value'] : null, $evaluation);

        return DB::transaction(function () use ($data, $evaluation, $enrollment, $status) {
            if ($this->repository->existsFor($evaluation->id, $enrollment->id)) {
                throw ValidationException::withMessages([
                    'enrollment_id' => 'Un Grade existe déjà pour cet élève sur cette évaluation (RG-003).',
                ]);
            }

            return Grade::create([
                'school_id' => $evaluation->school_id,
                'evaluation_id' => $evaluation->id,
                'enrollment_id' => $enrollment->id,
                'value' => $status === GradeStatusEnum::Absent->value ? 0 : ($data['value'] ?? null),
                'status' => $status,
                'comment' => $data['comment'] ?? null,
            ]);
        });
    }

    /**
     * Covers "saisir la note", "modifier la note", "marquer absent" and
     * "dispenser" — all four responsibilities the spec lists funnel through
     * this single PUT, matching the Routes API section which has no
     * dedicated endpoint for any of them.
     */
    public function update(Grade $grade, array $data): Grade
    {
        $this->assertMutable($grade);
        $grade->loadMissing('evaluation');

        $status = $data['status'] ?? $grade->status->value;
        $this->assertValueConsistent($status, isset($data['value']) ? (float) $data['value'] : null, $grade->evaluation);

        // RG-010: Absent always carries value 0. Dispensed carries no value
        // (RG-009: excluded from averages regardless of any stored number).
        $value = match ($status) {
            GradeStatusEnum::Absent->value => 0,
            GradeStatusEnum::Dispensed->value => null,
            default => $data['value'] ?? $grade->value,
        };

        $grade->update([
            'value' => $value,
            'status' => $status,
            'comment' => $data['comment'] ?? $grade->comment,
        ]);

        return $grade;
    }

    public function archive(Grade $grade): void
    {
        $grade->delete();
    }

    public function restore(Grade $grade): Grade
    {
        $grade->restore();

        return $grade;
    }

    /**
     * Subject averages (one per TeachingAssignment/Curriculum the
     * enrollment has Graded/Absent grades for) plus the coefficient-weighted
     * general average — spec's two formulas, computed on demand, nothing
     * persisted (rank is stored on Bulletin, not here, per the spec).
     */
    public function averagesFor(Enrollment $enrollment, ?string $academicPeriodId = null): array
    {
        $grades = $this->repository->countableGradesFor($enrollment->id, $academicPeriodId);
        $hasPending = $this->repository->hasPendingGrades($enrollment->id, $academicPeriodId);

        $bySubject = $grades->groupBy(fn (Grade $grade) => $grade->evaluation->teaching_assignment_id);

        $subjects = [];
        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($bySubject as $teachingAssignmentId => $subjectGrades) {
            $numerator = 0.0;
            $denominator = 0.0;

            foreach ($subjectGrades as $grade) {
                $coefficient = (float) ($grade->evaluation->coefficient ?? 1);
                $numerator += (float) $grade->value * $coefficient;
                $denominator += $coefficient;
            }

            $subjectAverage = $denominator > 0 ? round($numerator / $denominator, 2) : null;
            $curriculumCoefficient = (float) ($subjectGrades->first()->evaluation->teachingAssignment->curriculum->coefficient ?? 1);

            $subjects[] = [
                'teaching_assignment_id' => $teachingAssignmentId,
                'curriculum_id' => $subjectGrades->first()->evaluation->teachingAssignment->curriculum_id,
                'average' => $subjectAverage,
                'coefficient' => $curriculumCoefficient,
            ];

            if ($subjectAverage !== null) {
                $weightedSum += $subjectAverage * $curriculumCoefficient;
                $weightTotal += $curriculumCoefficient;
            }
        }

        return [
            'subjects' => $subjects,
            'general_average' => $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : null,
            'has_pending' => $hasPending,
        ];
    }
}
