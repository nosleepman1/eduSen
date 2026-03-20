<?php

namespace App\Repositories\Api\V1\Academic;

use App\Models\HolidayEvent;
use Carbon\Carbon;

class HolidayEventRepository
{
    /**
     * Must be called inside the same transaction that performs the create/update.
     * Locks every holiday row for the calendar (not just the overlapping ones) so
     * a concurrent transaction can't insert a fresh overlapping row while this one
     * is mid-flight — locking only the rows that already overlap wouldn't help
     * when the conflicting row doesn't exist yet (phantom read).
     */
    public function overlapsExisting(string $schoolCalendarId, Carbon|string $startDate, Carbon|string $endDate, ?string $excludeId = null): bool
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        return HolidayEvent::query()
            ->where('school_calendar_id', $schoolCalendarId)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate()
            ->get()
            ->contains(fn (HolidayEvent $existing) => $existing->start_date->lte($endDate) && $existing->end_date->gte($startDate));
    }
}
