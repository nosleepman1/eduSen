<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\ScheduleEntryStatusEnum;
use App\Models\ScheduleEntry;
use App\Models\TeachingAssignment;
use App\Models\Timetable;
use App\Repositories\Api\V1\Academic\ScheduleEntryRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleEntryService
{
    public function __construct(
        private readonly ScheduleEntryRepository $repository
    ) {}

    private function assertCompatible(Timetable $timetable, TeachingAssignment $assignment): void
    {
        if ($timetable->classroom_id !== $assignment->classroom_id) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => "Cette affectation pédagogique n'appartient pas à la classe de cet emploi du temps.",
            ]);
        }

        if (! $assignment->isActive()) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => "Cette affectation pédagogique n'est plus active.",
            ]);
        }
    }

    /**
     * RSE-003/RSE-004/RSE-005/RSE-006, in that order: the cheap chronology
     * check first, then the three conflict checks inside the locked
     * transaction so no concurrent request can slip an overlapping entry in
     * between the check and the insert.
     */
    public function create(array $data, Timetable $timetable, TeachingAssignment $assignment): array
    {
        $this->assertCompatible($timetable, $assignment);

        if (! Carbon::parse($data['start_time'])->lt(Carbon::parse($data['end_time']))) {
            throw ValidationException::withMessages([
                'end_time' => "L'heure de fin doit être supérieure à l'heure de début.",
            ]);
        }

        $entry = DB::transaction(function () use ($data, $timetable, $assignment) {
            $this->assertNoConflicts($data, $timetable, $assignment, null);

            return ScheduleEntry::create([
                'school_id' => $timetable->school_id,
                'timetable_id' => $timetable->id,
                'teaching_assignment_id' => $assignment->id,
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'room' => $data['room'] ?? null,
                'status' => ScheduleEntryStatusEnum::Active,
            ]);
        });

        return [$entry, $this->volumeWarning($assignment)];
    }

    /**
     * Day/time/room changes all go through here — the spec lists "déplacer",
     * "changer d'horaire" and "changer de salle" as responsibilities, not as
     * separate endpoints (the Routes API section only lists a plain PUT).
     * Past LessonSessions are never touched — moot today since LessonSession
     * (module 13) doesn't exist yet, but the rule is documented here for
     * whoever builds it: only future sessions get recalculated, this method
     * never rewrites history.
     */
    public function update(ScheduleEntry $entry, array $data): array
    {
        $dayOfWeek = $data['day_of_week'] ?? $entry->day_of_week->value;
        $startTime = $data['start_time'] ?? $entry->start_time;
        $endTime = $data['end_time'] ?? $entry->end_time;

        if (! Carbon::parse($startTime)->lt(Carbon::parse($endTime))) {
            throw ValidationException::withMessages([
                'end_time' => "L'heure de fin doit être supérieure à l'heure de début.",
            ]);
        }

        $entry = DB::transaction(function () use ($entry, $data, $dayOfWeek, $startTime, $endTime) {
            $this->assertNoConflicts(
                ['day_of_week' => $dayOfWeek, 'start_time' => $startTime, 'end_time' => $endTime, 'room' => $data['room'] ?? $entry->room],
                $entry->timetable,
                $entry->teachingAssignment,
                $entry->id
            );

            $entry->update([
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'room' => $data['room'] ?? $entry->room,
            ]);

            return $entry;
        });

        return [$entry, $this->volumeWarning($entry->teachingAssignment)];
    }

    private function assertNoConflicts(array $data, Timetable $timetable, TeachingAssignment $assignment, ?string $excludeId): void
    {
        if ($this->repository->hasClassroomConflict($assignment->classroom_id, $data['day_of_week'], $data['start_time'], $data['end_time'], $excludeId)) {
            throw ValidationException::withMessages([
                'start_time' => 'Cette classe a déjà un cours sur ce créneau (RSE-004).',
            ]);
        }

        if ($this->repository->hasTeacherConflict($assignment->teacher_id, $data['day_of_week'], $data['start_time'], $data['end_time'], $excludeId)) {
            throw ValidationException::withMessages([
                'start_time' => 'Cet enseignant a déjà un cours sur ce créneau (RSE-005).',
            ]);
        }

        if (! empty($data['room']) && $this->repository->hasRoomConflict($timetable->school_id, $data['room'], $data['day_of_week'], $data['start_time'], $data['end_time'], $excludeId)) {
            throw ValidationException::withMessages([
                'room' => 'Cette salle est déjà réservée sur ce créneau (RSE-006).',
            ]);
        }
    }

    /**
     * "Contrôle du volume horaire": a mismatch against the curriculum's
     * weekly_hours is a warning, never a blocker — the spec is explicit
     * ("La création reste autorisée").
     */
    private function volumeWarning(TeachingAssignment $assignment): ?string
    {
        $assignment->loadMissing('curriculum');
        $expectedMinutes = $assignment->curriculum->weekly_hours * 60;
        $scheduledMinutes = $this->repository->scheduledMinutesFor($assignment->id);

        if ($scheduledMinutes === $expectedMinutes) {
            return null;
        }

        $scheduledHours = round($scheduledMinutes / 60, 1);

        return "Le volume horaire planifié ({$scheduledHours}h) ne correspond pas aux {$assignment->curriculum->weekly_hours}h prévues par le programme pédagogique.";
    }

    /**
     * "Suppression d'un créneau possédant déjà des LessonSessions": archived
     * only, never hard-deleted — past sessions (once LessonSession exists)
     * stay attached to this row via soft-delete.
     */
    public function archive(ScheduleEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            $entry->update(['status' => ScheduleEntryStatusEnum::Archived]);
            $entry->delete();
        });
    }

    public function restore(ScheduleEntry $entry): ScheduleEntry
    {
        return DB::transaction(function () use ($entry) {
            $entry->restore();
            $entry->update(['status' => ScheduleEntryStatusEnum::Active]);

            return $entry;
        });
    }
}
