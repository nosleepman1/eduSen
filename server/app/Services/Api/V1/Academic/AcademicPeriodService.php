<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\AcademicPeriodStatusEnum;
use App\Events\AcademicPeriodActivated;
use App\Exceptions\Academic\AcademicPeriodAlreadyClosedException;
use App\Exceptions\Academic\AcademicPeriodOutOfRangeException;
use App\Exceptions\Academic\AcademicPeriodOverlapException;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Repositories\Api\V1\Academic\AcademicPeriodRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicPeriodService
{
    private const GENERATION_TEMPLATES = [
        'SEMESTER' => ['Semestre 1', 'Semestre 2'],
        'TRIMESTER' => ['Trimestre 1', 'Trimestre 2', 'Trimestre 3'],
        'QUARTER' => ['Quarter 1', 'Quarter 2', 'Quarter 3', 'Quarter 4'],
    ];

    public function __construct(
        private readonly AcademicPeriodRepository $repository
    ) {}

    public function create(array $data, AcademicYear $year): AcademicPeriod
    {
        $this->assertWithinYear($year, $data['start_date'], $data['end_date']);

        return DB::transaction(function () use ($data, $year) {
            if ($this->repository->overlapsExisting($year->id, $data['start_date'], $data['end_date'])) {
                throw new AcademicPeriodOverlapException();
            }

            return AcademicPeriod::create([
                'school_id' => $year->school_id,
                'academic_year_id' => $year->id,
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => AcademicPeriodStatusEnum::Draft,
            ]);
        });
    }

    public function update(AcademicPeriod $period, array $data): AcademicPeriod
    {
        if ($period->isClosed()) {
            throw new AcademicPeriodAlreadyClosedException();
        }

        $startDate = $data['start_date'] ?? $period->start_date;
        $endDate = $data['end_date'] ?? $period->end_date;

        $this->assertWithinYear($period->academicYear, $startDate, $endDate);

        return DB::transaction(function () use ($period, $data, $startDate, $endDate) {
            if ($this->repository->overlapsExisting($period->academic_year_id, $startDate, $endDate, $period->id)) {
                throw new AcademicPeriodOverlapException();
            }

            $period->update($data);

            return $period->fresh();
        });
    }

    public function activate(AcademicPeriod $period): AcademicPeriod
    {
        return DB::transaction(function () use ($period) {
            $currentlyActive = $this->repository->findActive($period->academic_year_id);

            if ($currentlyActive && $currentlyActive->id !== $period->id) {
                $currentlyActive->update(['status' => AcademicPeriodStatusEnum::Closed]);
            }

            $period->update(['status' => AcademicPeriodStatusEnum::Active]);

            AcademicPeriodActivated::dispatch($period);

            return $period;
        });
    }

    public function close(AcademicPeriod $period): AcademicPeriod
    {
        if ($period->isClosed()) {
            throw new AcademicPeriodAlreadyClosedException();
        }

        return DB::transaction(function () use ($period) {
            $period->update(['status' => AcademicPeriodStatusEnum::Closed]);

            return $period->fresh();
        });
    }

    public function archive(AcademicPeriod $period): void
    {
        if ($period->isActive()) {
            throw ValidationException::withMessages([
                'status' => "Une période active doit d'abord être clôturée avant d'être archivée.",
            ]);
        }

        DB::transaction(function () use ($period) {
            $period->update(['status' => AcademicPeriodStatusEnum::Archived]);
            $period->delete();
        });
    }

    public function restore(AcademicPeriod $period): AcademicPeriod
    {
        return DB::transaction(function () use ($period) {
            $period->restore();
            $period->update(['status' => AcademicPeriodStatusEnum::Closed]);

            return $period->fresh();
        });
    }

    /**
     * Generate the default set of periods for a mode (SEMESTER|TRIMESTER|QUARTER),
     * evenly splitting the academic year's date range.
     */
    public function generateDefaultPeriods(AcademicYear $year, string $mode): array
    {
        $names = self::GENERATION_TEMPLATES[$mode] ?? null;

        if ($names === null) {
            throw ValidationException::withMessages([
                'mode' => "Le mode de découpage doit être SEMESTER, TRIMESTER ou QUARTER.",
            ]);
        }

        return DB::transaction(function () use ($names, $year) {
            $totalDays = $year->start_date->diffInDays($year->end_date) + 1;
            $chunk = intdiv($totalDays, count($names));
            $cursor = $year->start_date->copy();
            $periods = [];

            foreach ($names as $index => $name) {
                $isLast = $index === count($names) - 1;
                $start = $cursor->copy();
                $end = $isLast ? $year->end_date->copy() : $cursor->copy()->addDays($chunk - 1);

                $periods[] = AcademicPeriod::create([
                    'school_id' => $year->school_id,
                    'academic_year_id' => $year->id,
                    'name' => $name,
                    'start_date' => $start,
                    'end_date' => $end,
                    'status' => AcademicPeriodStatusEnum::Draft,
                ]);

                $cursor = $end->copy()->addDay();
            }

            return $periods;
        });
    }

    private function assertWithinYear(AcademicYear $year, Carbon|string $startDate, Carbon|string $endDate): void
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        if ($startDate->gte($endDate)) {
            throw ValidationException::withMessages([
                'end_date' => "La date de fin doit être strictement postérieure à la date de début.",
            ]);
        }

        if ($startDate->lt($year->start_date) || $endDate->gt($year->end_date)) {
            throw new AcademicPeriodOutOfRangeException();
        }
    }
}
