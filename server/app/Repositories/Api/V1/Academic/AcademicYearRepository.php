<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\AcademicYearStatusEnum;
use App\Models\AcademicYear;
use Carbon\Carbon;

class AcademicYearRepository
{
    /**
     * Must be called inside the same transaction that performs the create/update.
     * Locks every year row for the school (not just the overlapping ones) so a
     * concurrent transaction can't insert a fresh overlapping row while this one
     * is mid-flight — locking only the rows that already overlap wouldn't help
     * when the conflicting row doesn't exist yet (phantom read).
     */
    public function overlapsExisting(string $schoolId, Carbon|string $startDate, Carbon|string $endDate, ?string $excludeId = null): bool
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->get()
            ->contains(fn (AcademicYear $existing) => $existing->start_date->lte($endDate) && $existing->end_date->gte($startDate));
    }

    public function findActive(string $schoolId): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('status', AcademicYearStatusEnum::Active)
            ->first();
    }
}
