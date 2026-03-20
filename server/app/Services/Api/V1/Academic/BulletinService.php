<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\BulletinStatusEnum;
use App\Models\AcademicPeriod;
use App\Models\Bulletin;
use App\Models\BulletinLine;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\TeachingAssignment;
use App\Repositories\Api\V1\Academic\BulletinRepository;
use App\Repositories\Api\V1\Academic\GradeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulletinService
{
    public function __construct(
        private readonly BulletinRepository $repository,
        private readonly GradeRepository $gradeRepository,
        private readonly GradeService $gradeService
    ) {}

    private function assertMutable(Bulletin $bulletin): void
    {
        if ($bulletin->isLocked()) {
            throw ValidationException::withMessages([
                'bulletin' => 'Ce bulletin est verrouillé et ne peut plus être modifié (RB-008).',
            ]);
        }
    }

    /**
     * RB-004/RB-005, in that order — cheapest, single-query check first.
     * Both are hard refusals ("Refus" in the spec's cas limites), unlike
     * Grade's own has_pending flag which is only ever an informational
     * warning.
     */
    private function assertGeneratable(Classroom $classroom, AcademicPeriod $period, Enrollment $enrollment): void
    {
        if ($this->repository->hasUnclosedEvaluations($classroom->id, $period->id)) {
            throw ValidationException::withMessages([
                'academic_period_id' => "Toutes les évaluations de la période doivent être clôturées ou verrouillées (RB-004).",
            ]);
        }

        if ($this->gradeRepository->hasPendingGrades($enrollment->id, $period->id)) {
            throw ValidationException::withMessages([
                'enrollment_id' => "Certaines notes de l'élève sont encore en attente de saisie (RB-005).",
            ]);
        }
    }

    private function mentionFor(?float $average): ?string
    {
        if ($average === null) {
            return null;
        }

        return match (true) {
            $average >= 16 => 'Très Bien',
            $average >= 14 => 'Bien',
            $average >= 12 => 'Assez Bien',
            $average >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }

    /**
     * Every subject actually assigned in the classroom gets a BulletinLine,
     * even one the student has no countable grade in yet (average stays
     * null) — "1 par matière du Curriculum" per the spec, not just the
     * subjects that happen to have grades.
     */
    private function buildLines(Bulletin $bulletin, Classroom $classroom, array $subjectAverages): void
    {
        $assignments = TeachingAssignment::query()
            ->where('classroom_id', $classroom->id)
            ->with('curriculum')
            ->get()
            ->unique('curriculum_id');

        $averagesByAssignment = collect($subjectAverages)->keyBy('teaching_assignment_id');

        foreach ($assignments as $assignment) {
            $match = $averagesByAssignment->get($assignment->id);
            $coefficient = (float) ($assignment->curriculum->coefficient ?? 1);
            $subjectAverage = $match['average'] ?? null;

            BulletinLine::create([
                'school_id' => $bulletin->school_id,
                'bulletin_id' => $bulletin->id,
                'curriculum_id' => $assignment->curriculum_id,
                'subject_average' => $subjectAverage,
                'coefficient' => $coefficient,
                'points' => $subjectAverage !== null ? round($subjectAverage * $coefficient, 2) : null,
            ]);
        }
    }

    /**
     * RB-007: rank among every enrollment of the same classroom, by
     * descending general average — computed on demand for the whole class,
     * not persisted anywhere (no classroom_period_stats cache table in
     * this implementation; the spec explicitly allows computing dynamically
     * as an alternative to that cache).
     */
    private function rankFor(Classroom $classroom, AcademicPeriod $period, string $enrollmentId, float $thisAverage): array
    {
        $peers = Enrollment::query()->where('classroom_id', $classroom->id)->get();

        $averages = $peers->map(function (Enrollment $peer) use ($period, $enrollmentId, $thisAverage) {
            if ($peer->id === $enrollmentId) {
                return $thisAverage;
            }

            return $this->gradeService->averagesFor($peer, $period->id)['general_average'];
        })->filter(fn ($average) => $average !== null)->sortDesc()->values();

        $rank = $averages->search(fn ($average) => abs($average - $thisAverage) < 0.001);

        return [
            'rank' => $rank === false ? null : $rank + 1,
            'total' => $averages->count(),
        ];
    }

    public function generateFor(Enrollment $enrollment, AcademicPeriod $period): Bulletin
    {
        $classroom = Classroom::findOrFail($enrollment->classroom_id);

        $this->assertGeneratable($classroom, $period, $enrollment);

        return DB::transaction(function () use ($enrollment, $period, $classroom) {
            if ($this->repository->existsFor($period->id, $enrollment->id)) {
                throw ValidationException::withMessages([
                    'enrollment_id' => 'Un bulletin existe déjà pour cet élève sur cette période (RB-003).',
                ]);
            }

            $averages = $this->gradeService->averagesFor($enrollment, $period->id);
            $generalAverage = $averages['general_average'];
            $rankInfo = $generalAverage !== null
                ? $this->rankFor($classroom, $period, $enrollment->id, $generalAverage)
                : ['rank' => null, 'total' => null];

            $bulletin = Bulletin::create([
                'school_id' => $enrollment->school_id,
                'academic_period_id' => $period->id,
                'enrollment_id' => $enrollment->id,
                'general_average' => $generalAverage,
                'class_rank' => $rankInfo['rank'],
                'total_students' => $rankInfo['total'],
                'mention' => $this->mentionFor($generalAverage),
                'status' => BulletinStatusEnum::Draft,
                'generated_at' => now(),
            ]);

            $this->buildLines($bulletin, $classroom, $averages['subjects']);

            return $bulletin;
        });
    }

    /**
     * RB-010: mass generation runs as a queued Job (App\Jobs\GenerateClassBulletinsJob)
     * rather than inline — this method is the per-student unit of work that
     * Job calls, kept here so both the single and mass paths share the
     * exact same generation logic instead of drifting apart.
     */
    public function generateForClass(Classroom $classroom, AcademicPeriod $period): array
    {
        $enrollments = Enrollment::query()
            ->where('classroom_id', $classroom->id)
            ->where('status', \App\Enums\EnrollmentStatusEnum::Active)
            ->get();

        $created = [];
        $failed = [];

        foreach ($enrollments as $enrollment) {
            try {
                $created[] = $this->generateFor($enrollment, $period);
            } catch (ValidationException $exception) {
                $failed[] = ['enrollment_id' => $enrollment->id, 'reason' => $exception->getMessage()];
            }
        }

        return ['created' => $created, 'failed' => $failed];
    }

    public function addComment(Bulletin $bulletin, ?string $comment): Bulletin
    {
        $this->assertMutable($bulletin);

        $bulletin->update(['class_council_comment' => $comment]);

        return $bulletin;
    }

    public function validate(Bulletin $bulletin, ?string $comment = null): Bulletin
    {
        $this->assertMutable($bulletin);

        if (! $bulletin->isDraft()) {
            throw ValidationException::withMessages([
                'bulletin' => 'Seul un bulletin en brouillon peut être validé.',
            ]);
        }

        $bulletin->update([
            'status' => BulletinStatusEnum::Validated,
            'class_council_comment' => $comment ?? $bulletin->class_council_comment,
        ]);

        return $bulletin;
    }

    public function publish(Bulletin $bulletin): Bulletin
    {
        $this->assertMutable($bulletin);

        if (! $bulletin->isValidated()) {
            throw ValidationException::withMessages([
                'bulletin' => 'Seul un bulletin validé peut être publié.',
            ]);
        }

        $bulletin->update(['status' => BulletinStatusEnum::Published, 'published_at' => now()]);

        return $bulletin;
    }

    public function lock(Bulletin $bulletin): Bulletin
    {
        $this->assertMutable($bulletin);

        if (! $bulletin->isPublished()) {
            throw ValidationException::withMessages([
                'bulletin' => 'Seul un bulletin publié peut être verrouillé.',
            ]);
        }

        $bulletin->update(['status' => BulletinStatusEnum::Locked]);

        return $bulletin;
    }

    /**
     * Soft-delete only, status untouched — same precedent as
     * Evaluation/Enrollment/TeachingAssignment: a Locked bulletin restores
     * back to Locked, never resets to Draft.
     */
    public function archive(Bulletin $bulletin): void
    {
        $bulletin->delete();
    }

    public function restore(Bulletin $bulletin): Bulletin
    {
        $bulletin->restore();

        return $bulletin;
    }

    /**
     * "classroom_period_stats" cache table from the spec's architecture
     * note is not implemented — the spec explicitly allows computing these
     * figures dynamically instead, which is what this does.
     */
    public function classroomSummary(Classroom $classroom, AcademicPeriod $period): array
    {
        $averages = Bulletin::query()
            ->where('academic_period_id', $period->id)
            ->whereHas('enrollment', fn ($query) => $query->where('classroom_id', $classroom->id))
            ->whereNotNull('general_average')
            ->pluck('general_average')
            ->map(fn ($value) => (float) $value);

        return [
            'class_average' => $averages->isNotEmpty() ? round($averages->avg(), 2) : null,
            'highest_average' => $averages->isNotEmpty() ? $averages->max() : null,
            'lowest_average' => $averages->isNotEmpty() ? $averages->min() : null,
            'total_bulletins' => $averages->count(),
        ];
    }
}
