<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\AttendanceStatusEnum;
use App\Enums\EnrollmentStatusEnum;
use App\Enums\JustificationStatusEnum;
use App\Enums\LessonSessionStatusEnum;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\LessonSession;
use App\Repositories\Api\V1\Academic\AttendanceRecordRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceRecordService
{
    private const CREATABLE_SESSION_STATUSES = [
        LessonSessionStatusEnum::InProgress,
        LessonSessionStatusEnum::Completed,
        LessonSessionStatusEnum::PendingValidation,
    ];

    public function __construct(
        private readonly AttendanceRecordRepository $repository
    ) {}

    private function assertCompatible(LessonSession $session, Enrollment $enrollment): void
    {
        $session->loadMissing('scheduleEntry.teachingAssignment');

        if ($enrollment->classroom_id !== $session->scheduleEntry->teachingAssignment->classroom_id) {
            throw ValidationException::withMessages([
                'enrollment_id' => "Cet élève n'est pas inscrit dans la classe de cette séance (RAR-010).",
            ]);
        }

        if (! in_array($session->status, self::CREATABLE_SESSION_STATUSES, true)) {
            throw ValidationException::withMessages([
                'lesson_session_id' => "Les présences ne peuvent être saisies que pendant ou après une séance (RAR-004).",
            ]);
        }
    }

    private function assertMutable(AttendanceRecord $record): void
    {
        $record->loadMissing('lessonSession');

        if ($record->lessonSession->isArchived()) {
            throw ValidationException::withMessages([
                'record' => 'Cette séance est archivée, ses présences ne peuvent plus être modifiées (RAR-005).',
            ]);
        }
    }

    private function assertStatusFieldsConsistent(array $data, string $status): void
    {
        if ($status === AttendanceStatusEnum::Late->value && empty($data['actual_arrival_time'])) {
            throw ValidationException::withMessages([
                'actual_arrival_time' => "L'heure d'arrivée réelle est obligatoire pour un retard (RAR-006).",
            ]);
        }

        if ($status === AttendanceStatusEnum::Excused->value && empty($data['justification'])) {
            throw ValidationException::withMessages([
                'justification' => 'La justification est obligatoire pour un statut Excusé (RAR-008).',
            ]);
        }
    }

    public function create(array $data, LessonSession $session, Enrollment $enrollment): AttendanceRecord
    {
        $this->assertCompatible($session, $enrollment);
        $this->assertStatusFieldsConsistent($data, $data['status'] ?? AttendanceStatusEnum::Absent->value);

        return DB::transaction(function () use ($data, $session, $enrollment) {
            if ($this->repository->existsFor($session->id, $enrollment->id)) {
                throw ValidationException::withMessages([
                    'enrollment_id' => 'Un enregistrement de présence existe déjà pour cet élève sur cette séance (RAR-003).',
                ]);
            }

            return AttendanceRecord::create([
                'school_id' => $session->school_id,
                'lesson_session_id' => $session->id,
                'enrollment_id' => $enrollment->id,
                'status' => $data['status'] ?? AttendanceStatusEnum::Absent,
                'actual_arrival_time' => $data['actual_arrival_time'] ?? null,
                'absence_reason' => $data['absence_reason'] ?? null,
                'justification' => $data['justification'] ?? null,
                'justification_status' => ($data['justification'] ?? null) ? JustificationStatusEnum::Pending : null,
                'comment' => $data['comment'] ?? null,
            ]);
        });
    }

    /**
     * Standard call-taking flow: one record per Active enrollment of the
     * classroom, defaulted to Absent — the teacher then flips the students
     * who are actually present rather than checking each one individually.
     * Existing records for the session are left untouched (idempotent).
     */
    public function bulkCreate(LessonSession $session): array
    {
        $session->loadMissing('scheduleEntry.teachingAssignment');

        if (! in_array($session->status, self::CREATABLE_SESSION_STATUSES, true)) {
            throw ValidationException::withMessages([
                'lesson_session_id' => "Les présences ne peuvent être saisies que pendant ou après une séance (RAR-004).",
            ]);
        }

        $classroomId = $session->scheduleEntry->teachingAssignment->classroom_id;

        return DB::transaction(function () use ($session, $classroomId) {
            $enrollments = Enrollment::query()
                ->where('classroom_id', $classroomId)
                ->where('status', EnrollmentStatusEnum::Active)
                ->get();

            $created = [];

            foreach ($enrollments as $enrollment) {
                if ($this->repository->existsFor($session->id, $enrollment->id)) {
                    continue;
                }

                $created[] = AttendanceRecord::create([
                    'school_id' => $session->school_id,
                    'lesson_session_id' => $session->id,
                    'enrollment_id' => $enrollment->id,
                    'status' => AttendanceStatusEnum::Absent,
                ]);
            }

            return $created;
        });
    }

    public function update(AttendanceRecord $record, array $data): AttendanceRecord
    {
        $this->assertMutable($record);

        $status = $data['status'] ?? $record->status->value;
        $this->assertStatusFieldsConsistent($data, $status);

        $record->update([
            'status' => $status,
            // RAR-006: only a Late record keeps an arrival time — any other
            // status clears it rather than leaving a stale value behind.
            'actual_arrival_time' => $status === AttendanceStatusEnum::Late->value
                ? ($data['actual_arrival_time'] ?? $record->actual_arrival_time)
                : null,
            'absence_reason' => $data['absence_reason'] ?? $record->absence_reason,
            'comment' => $data['comment'] ?? $record->comment,
        ]);

        return $record;
    }

    /**
     * "Soumettre une justification pour un élève Present → Refus": only an
     * Absent record can carry a justification request.
     */
    public function justify(AttendanceRecord $record, string $justification): AttendanceRecord
    {
        $this->assertMutable($record);

        if ($record->status !== AttendanceStatusEnum::Absent) {
            throw ValidationException::withMessages([
                'record' => 'Seule une absence peut faire l\'objet d\'une justification.',
            ]);
        }

        $record->update([
            'justification' => $justification,
            'justification_status' => JustificationStatusEnum::Pending,
        ]);

        return $record;
    }

    public function approveJustification(AttendanceRecord $record): AttendanceRecord
    {
        $this->assertMutable($record);

        if ($record->justification_status !== JustificationStatusEnum::Pending) {
            throw ValidationException::withMessages([
                'record' => 'Seule une justification en attente peut être approuvée.',
            ]);
        }

        $record->update([
            'justification_status' => JustificationStatusEnum::Approved,
            'status' => AttendanceStatusEnum::Excused,
        ]);

        return $record;
    }

    public function rejectJustification(AttendanceRecord $record): AttendanceRecord
    {
        $this->assertMutable($record);

        if ($record->justification_status !== JustificationStatusEnum::Pending) {
            throw ValidationException::withMessages([
                'record' => 'Seule une justification en attente peut être rejetée.',
            ]);
        }

        $record->update(['justification_status' => JustificationStatusEnum::Rejected]);

        return $record;
    }

    public function archive(AttendanceRecord $record): void
    {
        $record->delete();
    }

    public function restore(AttendanceRecord $record): AttendanceRecord
    {
        $record->restore();

        return $record;
    }

    /**
     * "Taux de présence": Present, Late, Excused and Dispensed all count as
     * accounted-for attendance — only unjustified Absent counts against the
     * rate. Documented here since the spec names the indicator without
     * pinning down the exact formula.
     */
    public function attendanceSummary(Enrollment $enrollment): array
    {
        $counts = $this->repository->countByStatus($enrollment->id);
        $total = array_sum($counts);
        $absent = $counts[AttendanceStatusEnum::Absent->value] ?? 0;

        return [
            'total' => $total,
            'present' => $counts[AttendanceStatusEnum::Present->value] ?? 0,
            'absent' => $absent,
            'late' => $counts[AttendanceStatusEnum::Late->value] ?? 0,
            'excused' => $counts[AttendanceStatusEnum::Excused->value] ?? 0,
            'dispensed' => $counts[AttendanceStatusEnum::Dispensed->value] ?? 0,
            'attendance_rate' => $total > 0 ? round(($total - $absent) / $total * 100, 1) : null,
        ];
    }
}
