<?php

namespace App\Repositories\Api\V1\Academic;

use App\Models\Level;
use Illuminate\Support\Collection;

class LevelRepository
{
    /**
     * Must be called inside the same transaction as the create/reorder
     * write. Locks every level row for the year so a concurrent create/
     * reorder can't race with this one — an unlocked MAX(display_order)
     * read would let two concurrent creates compute the same "next" value
     * (and Postgres doesn't allow FOR UPDATE on an aggregate query, so the
     * lock has to wrap a full row fetch instead of the MAX() itself).
     */
    public function lockAllForYear(string $academicYearId): Collection
    {
        return Level::query()
            ->where('academic_year_id', $academicYearId)
            ->lockForUpdate()
            ->get();
    }
}
