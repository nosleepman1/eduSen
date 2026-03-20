<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\LessonSessionStatusEnum;
use App\Models\LessonSession;
use App\Models\ScheduleEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LessonSessionRepository
{
    public function existsForDate(string $scheduleEntryId, string $date): bool
    {
        return LessonSession::query()
            ->where('schedule_entry_id', $scheduleEntryId)
            ->whereDate('session_date', $date)
            ->exists();
    }

    /**
     * Holiday/closure ranges for the academic year the entry belongs to,
     * flattened from every SchoolCalendar attached to that year — sessions
     * are never generated on a date any of these ranges cover.
     */
    public function holidayRangesFor(ScheduleEntry $entry): Collection
    {
        $academicYear = $entry->timetable->academicYear->loadMissing('schoolCalendars.holidayEvents');

        return $academicYear->schoolCalendars
            ->flatMap(fn ($calendar) => $calendar->holidayEvents)
            ->map(fn ($event) => [$event->start_date, $event->end_date]);
    }

    public function fallsWithinHoliday(Carbon $date, Collection $ranges): bool
    {
        return $ranges->contains(fn ($range) => $date->between($range[0], $range[1], true));
    }

    /**
     * Future, not-yet-started sessions for a schedule entry — the only ones
     * regenerate() is allowed to touch. Sessions already InProgress,
     * Completed, Cancelled, Postponed or Archived are historical and must
     * never be rewritten (spec: "les anciennes séances restent inchangées").
     */
    public function futureRegeneratable(string $scheduleEntryId): Collection
    {
        return LessonSession::query()
            ->where('schedule_entry_id', $scheduleEntryId)
            ->whereDate('session_date', '>=', Carbon::today())
            ->whereIn('status', [LessonSessionStatusEnum::Scheduled, LessonSessionStatusEnum::PendingStart])
            ->get();
    }
}
