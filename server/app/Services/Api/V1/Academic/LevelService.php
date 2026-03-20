<?php

namespace App\Services\Api\V1\Academic;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Repositories\Api\V1\Academic\LevelRepository;
use Illuminate\Support\Facades\DB;

class LevelService
{
    public function __construct(
        private readonly LevelRepository $repository
    ) {}

    public function create(array $data, AcademicYear $year): Level
    {
        return DB::transaction(function () use ($data, $year) {
            if (! isset($data['display_order'])) {
                $existing = $this->repository->lockAllForYear($year->id);
                $data['display_order'] = ((int) $existing->max('display_order')) + 1;
            }

            return Level::create([
                'school_id' => $year->school_id,
                'academic_year_id' => $year->id,
                'specialization_id' => $data['specialization_id'] ?? null,
                'name' => $data['name'],
                'code' => $data['code'],
                'display_order' => $data['display_order'],
                'description' => $data['description'] ?? null,
            ]);
        });
    }

    public function update(Level $level, array $data): Level
    {
        $level->update($data);

        return $level;
    }

    public function archive(Level $level): void
    {
        $level->delete();
    }

    public function restore(Level $level): Level
    {
        $level->restore();

        return $level;
    }

    /**
     * Reassigns display_order 1..n for the given ordered list of level IDs,
     * locked within a transaction so a concurrent create/reorder can't
     * interleave with a stale set of rows.
     */
    public function reorder(array $orderedIds, AcademicYear $year): array
    {
        return DB::transaction(function () use ($orderedIds, $year) {
            $levels = $this->repository->lockAllForYear($year->id)->keyBy('id');

            $updated = [];
            foreach ($orderedIds as $index => $id) {
                $level = $levels->get($id);

                if ($level) {
                    $level->update(['display_order' => $index + 1]);
                    $updated[] = $level;
                }
            }

            return $updated;
        });
    }
}
