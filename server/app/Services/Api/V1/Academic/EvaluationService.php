<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\AcademicPeriodStatusEnum;
use App\Enums\EvaluationStatusEnum;
use App\Enums\EvaluationTypeEnum;
use App\Models\AcademicPeriod;
use App\Models\Evaluation;
use App\Models\LessonSession;
use App\Models\TeachingAssignment;
use App\Repositories\Api\V1\Academic\EvaluationRepository;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(
        private readonly EvaluationRepository $repository,
        private readonly GradeService $gradeService
    ) {}

    private function assertMutable(Evaluation $evaluation): void
    {
        if ($evaluation->isLocked()) {
            throw ValidationException::withMessages([
                'evaluation' => 'Cette évaluation est verrouillée et ne peut plus être modifiée (REV-008).',
            ]);
        }
    }

    private function assertLessonSessionCompatible(?LessonSession $lessonSession, TeachingAssignment $assignment): void
    {
        if ($lessonSession === null) {
            return;
        }

        $lessonSession->loadMissing('scheduleEntry');

        if ($lessonSession->scheduleEntry->teaching_assignment_id !== $assignment->id) {
            throw ValidationException::withMessages([
                'lesson_session_id' => "La séance liée doit appartenir à la même affectation pédagogique (REV-011).",
            ]);
        }
    }

    /**
     * REV-005/REV-007, REV-004, REV-006, in that order — cheapest checks
     * first. Returns [Evaluation, ?string $warning] since "deux Examens sur
     * la même période" is a non-blocking warning, not a refusal.
     */
    public function create(array $data, AcademicPeriod $period, TeachingAssignment $assignment, ?LessonSession $lessonSession): array
    {
        if ($period->status !== AcademicPeriodStatusEnum::Active) {
            throw ValidationException::withMessages([
                'academic_period_id' => "La période académique doit être active pour créer une évaluation (REV-005/REV-007).",
            ]);
        }

        $evaluationDate = Carbon::parse($data['evaluation_date']);

        if ($evaluationDate->lt($period->start_date) || $evaluationDate->gt($period->end_date)) {
            throw ValidationException::withMessages([
                'evaluation_date' => "La date de l'évaluation doit être comprise dans la période académique (REV-004).",
            ]);
        }

        if ((float) $data['max_score'] <= 0) {
            throw ValidationException::withMessages([
                'max_score' => 'Le barème doit être strictement positif (REV-006).',
            ]);
        }

        $this->assertLessonSessionCompatible($lessonSession, $assignment);

        $evaluation = Evaluation::create([
            'school_id' => $assignment->school_id,
            'academic_year_id' => $assignment->academic_year_id,
            'academic_period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'lesson_session_id' => $lessonSession?->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'evaluation_date' => $data['evaluation_date'],
            'max_score' => $data['max_score'],
            'coefficient' => $data['coefficient'] ?? null,
            'status' => EvaluationStatusEnum::Draft,
        ]);

        $warning = null;
        if ($evaluation->type === EvaluationTypeEnum::Examen
            && $this->repository->hasAnotherExam($assignment->id, $period->id, $evaluation->id)) {
            $warning = 'Un autre examen existe déjà pour cette matière sur cette période.';
        }

        return [$evaluation, $warning];
    }

    public function update(Evaluation $evaluation, array $data): Evaluation
    {
        $this->assertMutable($evaluation);

        $evaluation->update([
            'title' => $data['title'] ?? $evaluation->title,
            'description' => $data['description'] ?? $evaluation->description,
            'evaluation_date' => $data['evaluation_date'] ?? $evaluation->evaluation_date,
            'max_score' => $data['max_score'] ?? $evaluation->max_score,
            'coefficient' => $data['coefficient'] ?? $evaluation->coefficient,
        ]);

        return $evaluation;
    }

    public function open(Evaluation $evaluation): Evaluation
    {
        $this->assertMutable($evaluation);

        if (! $evaluation->isDraft()) {
            throw ValidationException::withMessages([
                'evaluation' => 'Seule une évaluation en brouillon peut être ouverte.',
            ]);
        }

        $evaluation->update(['status' => EvaluationStatusEnum::Open]);

        // Closes the loop left open in the ScheduleEntry/LessonSession
        // branches of this same suite: Grade now exists, so opening an
        // Evaluation generates its Pending Grades exactly as the spec's
        // workflow describes, instead of deferring it again.
        $this->gradeService->bulkGenerateFor($evaluation);

        return $evaluation;
    }

    public function close(Evaluation $evaluation): Evaluation
    {
        $this->assertMutable($evaluation);

        if (! $evaluation->isOpen()) {
            throw ValidationException::withMessages([
                'evaluation' => 'Seule une évaluation ouverte peut être clôturée.',
            ]);
        }

        $evaluation->update(['status' => EvaluationStatusEnum::Closed]);

        return $evaluation;
    }

    public function lock(Evaluation $evaluation): Evaluation
    {
        $this->assertMutable($evaluation);

        if (! $evaluation->isClosed()) {
            throw ValidationException::withMessages([
                'evaluation' => 'Seule une évaluation clôturée peut être verrouillée.',
            ]);
        }

        $evaluation->update(['status' => EvaluationStatusEnum::Locked]);

        return $evaluation;
    }

    /**
     * Soft-delete only, status untouched — a Locked evaluation must restore
     * back to Locked (its Grades already feed Bulletin math), not reset to
     * Draft. Same precedent as Enrollment/TeachingAssignment archive().
     */
    public function archive(Evaluation $evaluation): void
    {
        $evaluation->delete();
    }

    public function restore(Evaluation $evaluation): Evaluation
    {
        $evaluation->restore();

        return $evaluation;
    }
}
