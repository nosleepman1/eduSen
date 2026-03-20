<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\AcademicPeriodStatusEnum;
use App\Models\AcademicPeriod;
use Carbon\Carbon;

class AcademicPeriodRepository
{
    /**
     * Must be called inside the same transaction that performs the create/update.
     * Locks every period row for the year (not just the overlapping ones) so a
     * concurrent transaction can't insert a fresh overlapping row while this one
     * is mid-flight — locking only the rows that already overlap wouldn't help
     * when the conflicting row doesn't exist yet (phantom read).
     */
   
    public function overlapsExisting(string $academicYearId, Carbon|string $startDate, Carbon|string $endDate, ?string $excludeId = null): bool
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        return AcademicPeriod::query()
            ->where('academic_year_id', $academicYearId)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->get()
            ->contains(fn (AcademicPeriod $existing) => $existing->start_date->lte($endDate) && $existing->end_date->gte($startDate));
    }

    public function findActive(string $academicYearId): ?AcademicPeriod
    {
        return AcademicPeriod::query()
            ->where('academic_year_id', $academicYearId)
            ->where('status', AcademicPeriodStatusEnum::Active)
            ->first();
    }
}
