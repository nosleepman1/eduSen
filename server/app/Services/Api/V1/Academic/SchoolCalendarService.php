<?php

namespace App\Services\Api\V1\Academic;

use App\Exceptions\Academic\HolidayEventOverlapException;
use App\Models\AcademicYear;
use App\Models\HolidayEvent;
use App\Models\SchoolCalendar;
use App\Repositories\Api\V1\Academic\HolidayEventRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolCalendarService
{
    public function __construct(
        private readonly HolidayEventRepository $repository
    ) {}

    public function createForYear(AcademicYear $year, string $name): SchoolCalendar
    {
        return DB::transaction(function () use ($year, $name) {
            return SchoolCalendar::create([
                'school_id' => $year->school_id,
                'academic_year_id' => $year->id,
                'name' => $name,
            ]);
        });
    }

    public function addHoliday(SchoolCalendar $calendar, array $data): HolidayEvent
    {
        $this->assertDatesConsistent($data['start_date'], $data['end_date']);

        return DB::transaction(function () use ($calendar, $data) {
            if ($this->repository->overlapsExisting($calendar->id, $data['start_date'], $data['end_date'])) {
                throw new HolidayEventOverlapException();
            }

            return HolidayEvent::create([
                'school_id' => $calendar->school_id,
                'school_calendar_id' => $calendar->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_estimated' => $data['is_estimated'] ?? false,
            ]);
            // NOTE: la propagation en cascade sur les LessonSession (RPH-004) est hors scope
            // tant que le module LessonSession (Phase 5) n'existe pas.
        });
    }

    public function updateHoliday(HolidayEvent $holiday, array $data): HolidayEvent
    {
        $startDate = $data['start_date'] ?? $holiday->start_date;
        $endDate = $data['end_date'] ?? $holiday->end_date;

        $this->assertDatesConsistent($startDate, $endDate);

        return DB::transaction(function () use ($holiday, $data, $startDate, $endDate) {
            if ($this->repository->overlapsExisting($holiday->school_calendar_id, $startDate, $endDate, $holiday->id)) {
                throw new HolidayEventOverlapException();
            }

            $holiday->update($data);

            return $holiday->fresh();
        });
    }

    public function deleteHoliday(HolidayEvent $holiday): void
    {
        $holiday->delete();
    }

    private function assertDatesConsistent(Carbon|string $startDate, Carbon|string $endDate): void
    {
        if (Carbon::parse($startDate)->gt(Carbon::parse($endDate))) {
            throw ValidationException::withMessages([
                'end_date' => "La date de fin doit être postérieure ou égale à la date de début.",
            ]);
        }
    }
}
