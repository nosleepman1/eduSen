<?php

namespace App\Repositories\Api\V1\Academic;

use App\Models\AttendanceRecord;

class AttendanceRecordRepository
{
    /**
     * RAR-003, locked to guard the same phantom-read race as every other
     * unique-combination check in this domain.
     */
    public function existsFor(string $lessonSessionId, string $enrollmentId): bool
    {
        return AttendanceRecord::query()
            ->where('lesson_session_id', $lessonSessionId)
            ->where('enrollment_id', $enrollmentId)
            ->lockForUpdate()
            ->exists();
    }

    public function countByStatus(string $enrollmentId): array
    {
        return AttendanceRecord::query()
            ->where('enrollment_id', $enrollmentId)
            ->get()
            ->countBy(fn (AttendanceRecord $record) => $record->status->value)
            ->toArray();
    }
}
