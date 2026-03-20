<?php

namespace App\Repositories\Api\V1\Academic;

use App\Enums\ScheduleEntryStatusEnum;
use App\Models\ScheduleEntry;
use Illuminate\Database\Eloquent\Builder;

class ScheduleEntryRepository
{
    /**
     * RSE-004: same classroom, same day, overlapping time range. Locks the
     * candidate rows so two concurrent requests can't both pass the check
     * and insert overlapping entries — same phantom-read guard as every
     * other *ExistsForCombination-shaped check in this codebase.
     */
    public function hasClassroomConflict(string $classroomId, string $dayOfWeek, string $startTime, string $endTime, ?string $excludeId = null): bool
    {
        return $this->overlapping($dayOfWeek, $startTime, $endTime, $excludeId)
            ->whereHas('teachingAssignment', fn ($query) => $query->where('classroom_id', $classroomId))
            ->exists();
    }

    /**
     * RSE-005: same teacher, same day, overlapping time range — regardless
     * of which classroom, since a teacher can't physically be in two places.
     */
    public function hasTeacherConflict(string $teacherId, string $dayOfWeek, string $startTime, string $endTime, ?string $excludeId = null): bool
    {
        return $this->overlapping($dayOfWeek, $startTime, $endTime, $excludeId)
            ->whereHas('teachingAssignment', fn ($query) => $query->where('teacher_id', $teacherId))
            ->exists();
    }

    /**
     * RSE-006: same room (a plain string, not a modeled resource yet), same
     * day, overlapping time range, scoped to the school.
     */
    public function hasRoomConflict(string $schoolId, string $room, string $dayOfWeek, string $startTime, string $endTime, ?string $excludeId = null): bool
    {
        return $this->overlapping($dayOfWeek, $startTime, $endTime, $excludeId)
            ->where('school_id', $schoolId)
            ->where('room', $room)
            ->exists();
    }

    /**
     * Two ranges [s1,e1) and [s2,e2) overlap iff s1 < e2 AND s2 < e1 — the
     * standard interval-overlap test, applied here as start_time < :end AND
     * end_time > :start against the stored range.
     */
    private function overlapping(string $dayOfWeek, string $startTime, string $endTime, ?string $excludeId): Builder
    {
        return ScheduleEntry::query()
            ->where('day_of_week', $dayOfWeek)
            ->where('status', ScheduleEntryStatusEnum::Active)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->lockForUpdate();
    }

    public function scheduledMinutesFor(string $teachingAssignmentId): int
    {
        return ScheduleEntry::query()
            ->where('teaching_assignment_id', $teachingAssignmentId)
            ->where('status', ScheduleEntryStatusEnum::Active)
            ->get()
            ->sum(fn (ScheduleEntry $entry) => $entry->duration_minutes);
    }
}
