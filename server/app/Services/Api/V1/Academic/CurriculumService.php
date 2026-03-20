<?php

namespace App\Services\Api\V1\Academic;

use App\Exceptions\Academic\CurriculumSubjectConflictException;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Level;
use App\Repositories\Api\V1\Academic\CurriculumRepository;
use Illuminate\Support\Facades\DB;

class CurriculumService
{
    public function __construct(
        private readonly CurriculumRepository $repository
    ) {}

    public function create(array $data, Level $level): Curriculum
    {
        return DB::transaction(function () use ($data, $level) {
            $specializationId = $data['specialization_id'] ?? null;

            if ($this->repository->existsForCombination($level->academic_year_id, $level->id, $specializationId, $data['subject_id'])) {
                throw new CurriculumSubjectConflictException();
            }

            return Curriculum::create([
                'school_id' => $level->school_id,
                'academic_year_id' => $level->academic_year_id,
                'level_id' => $level->id,
                'specialization_id' => $specializationId,
                'subject_id' => $data['subject_id'],
                'coefficient' => $data['coefficient'],
                'weekly_hours' => $data['weekly_hours'],
                'display_order' => $data['display_order'] ?? null,
            ]);
        });
    }

    public function update(Curriculum $curriculum, array $data): Curriculum
    {
        $subjectId = $data['subject_id'] ?? $curriculum->subject_id;
        $specializationId = array_key_exists('specialization_id', $data) ? $data['specialization_id'] : $curriculum->specialization_id;

        return DB::transaction(function () use ($curriculum, $data, $subjectId, $specializationId) {
            if ($this->repository->existsForCombination(
                $curriculum->academic_year_id, $curriculum->level_id, $specializationId, $subjectId, $curriculum->id
            )) {
                throw new CurriculumSubjectConflictException();
            }

            $curriculum->update($data);

            return $curriculum;
        });
    }

    public function archive(Curriculum $curriculum): void
    {
        $curriculum->delete();
    }

    public function restore(Curriculum $curriculum): Curriculum
    {
        $curriculum->restore();

        return $curriculum;
    }

    /**
     * Copies every curriculum line from one academic year to another,
     * mapping levels by code (stable across years, unlike level_id).
     * Lines whose level has no counterpart in the target year, or that
     * would violate RCU-005 there, are skipped rather than failing the
     * whole operation — duplication is meant to be a best-effort starting
     * point the director then adjusts manually.
     */
    public function duplicate(AcademicYear $from, AcademicYear $to): array
    {
        return DB::transaction(function () use ($from, $to) {
            $targetLevelsByCode = Level::query()
                ->where('academic_year_id', $to->id)
                ->get()
                ->keyBy('code');

            $created = [];
            $skipped = [];

            foreach ($this->repository->forYear($from->id) as $sourceLine) {
                if (! $sourceLine->level) {
                    // The source Level was archived (soft-deleted) after this
                    // curriculum line was created; belongsTo() respects the
                    // SoftDeletingScope, so the relation resolves to null.
                    $skipped[] = ['curriculum_id' => $sourceLine->id, 'reason' => 'source_level_archived'];
                    continue;
                }

                $targetLevel = $targetLevelsByCode->get($sourceLine->level->code);

                if (! $targetLevel) {
                    $skipped[] = ['curriculum_id' => $sourceLine->id, 'reason' => 'no_matching_level'];
                    continue;
                }

                if ($this->repository->existsForCombination(
                    $to->id, $targetLevel->id, $sourceLine->specialization_id, $sourceLine->subject_id
                )) {
                    $skipped[] = ['curriculum_id' => $sourceLine->id, 'reason' => 'already_exists'];
                    continue;
                }

                $created[] = Curriculum::create([
                    'school_id' => $to->school_id,
                    'academic_year_id' => $to->id,
                    'level_id' => $targetLevel->id,
                    'specialization_id' => $sourceLine->specialization_id,
                    'subject_id' => $sourceLine->subject_id,
                    'coefficient' => $sourceLine->coefficient,
                    'weekly_hours' => $sourceLine->weekly_hours,
                    'display_order' => $sourceLine->display_order,
                ]);
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }
}
