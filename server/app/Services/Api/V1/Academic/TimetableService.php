<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\TimetableStatusEnum;
use App\Models\Classroom;
use App\Models\Timetable;
use App\Repositories\Api\V1\Academic\TimetableRepository;
use Illuminate\Support\Facades\DB;

class TimetableService
{
    public function __construct(
        private readonly TimetableRepository $repository
    ) {}

    public function create(array $data, Classroom $classroom): Timetable
    {
        return Timetable::create([
            'school_id' => $classroom->school_id,
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => TimetableStatusEnum::Draft,
        ]);
    }

    public function update(Timetable $timetable, array $data): Timetable
    {
        $timetable->update([
            'name' => $data['name'] ?? $timetable->name,
            'description' => $data['description'] ?? $timetable->description,
        ]);

        return $timetable;
    }

    /**
     * RTT-004 + cas limite: activating a timetable automatically deactivates
     * whichever one was previously Active for the same classroom — the
     * caller never has to deactivate it manually first.
     */
    public function activate(Timetable $timetable): Timetable
    {
        return DB::transaction(function () use ($timetable) {
            $previouslyActive = $this->repository->activeForClassroom($timetable->classroom_id, $timetable->id);
            $previouslyActive?->update(['status' => TimetableStatusEnum::Draft]);

            $timetable->update(['status' => TimetableStatusEnum::Active]);

            return $timetable;
        });
    }

    /**
     * Clones the container only. ScheduleEntry (module 12) doesn't exist yet
     * in this codebase, so there is nothing to copy into the duplicate yet —
     * once that domain is built, its own duplication logic should extend
     * this method rather than silently pretending entries were copied.
     */
    public function duplicate(Timetable $source): Timetable
    {
        return Timetable::create([
            'school_id' => $source->school_id,
            'academic_year_id' => $source->academic_year_id,
            'classroom_id' => $source->classroom_id,
            'name' => $source->name . ' (copie)',
            'description' => $source->description,
            'status' => TimetableStatusEnum::Draft,
        ]);
    }

    public function archive(Timetable $timetable): void
    {
        DB::transaction(function () use ($timetable) {
            $timetable->update(['status' => TimetableStatusEnum::Archived]);
            $timetable->delete();
        });
    }

    public function restore(Timetable $timetable): Timetable
    {
        return DB::transaction(function () use ($timetable) {
            $timetable->restore();
            $timetable->update(['status' => TimetableStatusEnum::Draft]);

            return $timetable;
        });
    }
}
