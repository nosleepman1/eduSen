<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\TimetableStatusEnum;
use App\Models\Timetable;

class TimetableRepository
{
    /**
     * RTT-004: at most one Active timetable per classroom. Locked so a
     * concurrent activate() can't race between reading the previous active
     * row and flipping it to Draft — same guard shape as
     * ClassroomRepository::existsForCombination.
     */
    public function activeForClassroom(string $classroomId, ?string $excludeId = null): ?Timetable
    {
        return Timetable::query()
            ->where('classroom_id', $classroomId)
            ->where('status', TimetableStatusEnum::Active)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->first();
    }
}
