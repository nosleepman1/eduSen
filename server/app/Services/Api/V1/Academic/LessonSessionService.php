<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\LessonSessionStatusEnum;
use App\Models\LessonSession;
use App\Models\ScheduleEntry;
use App\Repositories\Api\V1\Academic\LessonSessionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LessonSessionService
{
    public function __construct(
        private readonly LessonSessionRepository $repository
    ) {}

    /**
     * Generates one Scheduled LessonSession per occurrence of the entry's
     * day_of_week between $from (inclusive) and the earlier of the academic
     * year's end_date / the classroom's end_date_override, skipping any
     * date already covered by a SchoolCalendar holiday range or that
     * already has a session (idempotent — safe to call again).
     */
    public function generateFor(ScheduleEntry $entry, ?Carbon $from = null): array
    {
        $entry->loadMissing('timetable.academicYear', 'timetable.classroom');
        $academicYear = $entry->timetable->academicYear;
        $classroom = $entry->timetable->classroom;

        $yearStart = Carbon::parse($academicYear->start_date);
        $start = $from !== null && $from->gt($yearStart) ? $from : $yearStart;
        $end = Carbon::parse($classroom->end_date_override ?? $academicYear->end_date);
        $holidayRanges = $this->repository->holidayRangesFor($entry);

        $created = [];
        $skippedHoliday = 0;
        $skippedExisting = 0;

        $isoTarget = $this->isoDayOfWeek($entry->day_of_week->value);
        $cursor = $start->copy()->addDays(($isoTarget - $start->dayOfWeekIso + 7) % 7);

        while ($cursor->lte($end)) {
            if ($this->repository->fallsWithinHoliday($cursor, $holidayRanges)) {
                $skippedHoliday++;
            } elseif ($this->repository->existsForDate($entry->id, $cursor->toDateString())) {
                $skippedExisting++;
            } else {
                $created[] = LessonSession::create([
                    'school_id' => $entry->school_id,
                    'schedule_entry_id' => $entry->id,
                    'session_date' => $cursor->toDateString(),
                    'scheduled_start_time' => $entry->start_time,
                    'scheduled_end_time' => $entry->end_time,
                    'status' => LessonSessionStatusEnum::Scheduled,
                ]);
            }

            $cursor->addWeek();
        }

        return ['created' => $created, 'skipped_holiday' => $skippedHoliday, 'skipped_existing' => $skippedExisting];
    }

    /**
     * Only future, not-yet-started sessions are wiped and rebuilt — past
     * and in-flight sessions are never touched, per the spec's rule that
     * editing a ScheduleEntry must never rewrite history.
     */
    public function regenerateFor(ScheduleEntry $entry): array
    {
        return DB::transaction(function () use ($entry) {
            $this->repository->futureRegeneratable($entry->id)->each(fn (LessonSession $session) => $session->delete());

            return $this->generateFor($entry, Carbon::today());
        });
    }

    private function isoDayOfWeek(string $dayOfWeek): int
    {
        return match ($dayOfWeek) {
            'MONDAY' => 1, 'TUESDAY' => 2, 'WEDNESDAY' => 3, 'THURSDAY' => 4,
            'FRIDAY' => 5, 'SATURDAY' => 6, 'SUNDAY' => 7,
        };
    }

    /**
     * Lazily advances Scheduled -> PendingStart -> PendingValidation based on
     * wall-clock time against the session's own scheduled window. There is
     * no scheduler/queue worker wired up in this codebase yet to do this in
     * the background, so every read path calls this first — it is always
     * called before a session is returned or acted upon, so the status is
     * never stale by more than the time between two requests.
     */
    public function tick(LessonSession $session): LessonSession
    {
        if (in_array($session->status, [LessonSessionStatusEnum::Scheduled, LessonSessionStatusEnum::PendingStart], true)) {
            $now = Carbon::now();

            if ($session->status === LessonSessionStatusEnum::Scheduled && $now->gte($session->scheduledStartsAt())) {
                $session->update(['status' => LessonSessionStatusEnum::PendingStart]);
            }

            if ($session->status === LessonSessionStatusEnum::PendingStart && $now->gte($session->scheduledEndsAt())) {
                $session->update(['status' => LessonSessionStatusEnum::PendingValidation]);
            }
        }

        return $session;
    }

    private function assertMutable(LessonSession $session): void
    {
        if ($session->isArchived()) {
            throw ValidationException::withMessages([
                'session' => 'Cette séance est archivée et ne peut plus être modifiée (RLS-006).',
            ]);
        }
    }

    public function start(LessonSession $session): LessonSession
    {
        $this->assertMutable($session);
        $this->tick($session);

        if (! in_array($session->status, [LessonSessionStatusEnum::Scheduled, LessonSessionStatusEnum::PendingStart], true)) {
            throw ValidationException::withMessages([
                'session' => 'Cette séance ne peut pas être démarrée dans son état actuel.',
            ]);
        }

        $session->update([
            'actual_start' => Carbon::now(),
            'status' => LessonSessionStatusEnum::InProgress,
        ]);

        return $session;
    }

    /**
     * RLS-005: once Completed, a session can never go back to InProgress —
     * enforced simply by finish() being the only path to Completed and
     * requiring InProgress first.
     */
    public function finish(LessonSession $session): LessonSession
    {
        $this->assertMutable($session);

        if ($session->status !== LessonSessionStatusEnum::InProgress) {
            throw ValidationException::withMessages([
                'session' => 'Seule une séance en cours peut être terminée.',
            ]);
        }

        $session->update([
            'actual_end' => Carbon::now(),
            'status' => LessonSessionStatusEnum::Completed,
        ]);

        return $session;
    }

    public function cancel(LessonSession $session, string $reason, ?string $comment = null): LessonSession
    {
        $this->assertMutable($session);
        $this->tick($session);
        $this->assertCancellableOrPostponable($session);

        $session->update([
            'status' => LessonSessionStatusEnum::Cancelled,
            'cancellation_reason' => $reason,
            'admin_comment' => $comment,
        ]);

        return $session;
    }

    public function postpone(LessonSession $session, ?string $comment = null): LessonSession
    {
        $this->assertMutable($session);
        $this->tick($session);
        $this->assertCancellableOrPostponable($session);

        $session->update([
            'status' => LessonSessionStatusEnum::Postponed,
            'admin_comment' => $comment,
        ]);

        return $session;
    }

    private function assertCancellableOrPostponable(LessonSession $session): void
    {
        $allowed = [
            LessonSessionStatusEnum::Scheduled,
            LessonSessionStatusEnum::PendingStart,
            LessonSessionStatusEnum::PendingValidation,
        ];

        if (! in_array($session->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'session' => 'Une séance en cours ou déjà terminée ne peut pas être annulée ou reportée.',
            ]);
        }
    }

    /**
     * Administrative decision for a session that was never started
     * (spec's "Validation administrative"): the pedagogical lead chooses
     * Completed, Cancelled or Postponed once a session sits in
     * PendingValidation.
     */
    public function validate(LessonSession $session, string $decision, ?string $reason = null, ?string $comment = null): LessonSession
    {
        $this->assertMutable($session);
        $this->tick($session);

        if ($session->status !== LessonSessionStatusEnum::PendingValidation) {
            throw ValidationException::withMessages([
                'session' => 'Seule une séance en attente de validation peut être validée administrativement.',
            ]);
        }

        return match ($decision) {
            'COMPLETED' => $this->completeAdministratively($session, $comment),
            'CANCELLED' => $this->cancelWithoutRetick($session, $reason, $comment),
            'POSTPONED' => $this->postponeWithoutRetick($session, $comment),
        };
    }

    private function completeAdministratively(LessonSession $session, ?string $comment): LessonSession
    {
        $session->update(['status' => LessonSessionStatusEnum::Completed, 'admin_comment' => $comment]);

        return $session;
    }

    private function cancelWithoutRetick(LessonSession $session, ?string $reason, ?string $comment): LessonSession
    {
        if (! $reason) {
            throw ValidationException::withMessages(['reason' => 'Le motif est obligatoire pour annuler une séance.']);
        }

        $session->update(['status' => LessonSessionStatusEnum::Cancelled, 'cancellation_reason' => $reason, 'admin_comment' => $comment]);

        return $session;
    }

    private function postponeWithoutRetick(LessonSession $session, ?string $comment): LessonSession
    {
        $session->update(['status' => LessonSessionStatusEnum::Postponed, 'admin_comment' => $comment]);

        return $session;
    }

    public function restore(LessonSession $session): LessonSession
    {
        $session->restore();

        return $session;
    }
}
