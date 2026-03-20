<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\AcademicYearStatusEnum;
use App\Events\AcademicYearActivated;
use App\Events\AcademicYearClosed;
use App\Exceptions\Academic\AcademicYearAlreadyClosedException;
use App\Exceptions\Academic\AcademicYearOverlapException;
use App\Models\AcademicYear;
use App\Repositories\Api\V1\Academic\AcademicYearRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearService
{
    public function __construct(
        private readonly AcademicYearRepository $repository
    ) {}

    public function create(array $data, string $schoolId): AcademicYear
    {
        $this->assertDateRangeValid($data['start_date'], $data['end_date']);

        return DB::transaction(function () use ($data, $schoolId) {
            if ($this->repository->overlapsExisting($schoolId, $data['start_date'], $data['end_date'])) {
                throw new AcademicYearOverlapException();
            }

            return AcademicYear::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => AcademicYearStatusEnum::Draft,
            ]);
        });
    }

    public function update(AcademicYear $year, array $data): AcademicYear
    {
        if ($year->isClosed()) {
            throw new AcademicYearAlreadyClosedException();
        }

        $startDate = $data['start_date'] ?? $year->start_date;
        $endDate = $data['end_date'] ?? $year->end_date;

        $this->assertDateRangeValid($startDate, $endDate);

        return DB::transaction(function () use ($year, $data, $startDate, $endDate) {
            if ($this->repository->overlapsExisting($year->school_id, $startDate, $endDate, $year->id)) {
                throw new AcademicYearOverlapException();
            }

            $year->update($data);

            return $year->fresh();
        });
    }

    private function assertDateRangeValid(Carbon|string $startDate, Carbon|string $endDate): void
    {
        if (Carbon::parse($startDate)->gte(Carbon::parse($endDate))) {
            throw ValidationException::withMessages([
                'end_date' => "La date de fin doit être strictement postérieure à la date de début.",
            ]);
        }
    }

    public function activate(AcademicYear $year): AcademicYear
    {
        return DB::transaction(function () use ($year) {
            $this->closeOtherActiveYears($year->school_id, $year->id);

            $year->update(['status' => AcademicYearStatusEnum::Active]);

            AcademicYearActivated::dispatch($year);

            return $year;
        });
    }

    public function close(AcademicYear $year): AcademicYear
    {
        if ($year->isClosed()) {
            throw new AcademicYearAlreadyClosedException();
        }

        return DB::transaction(function () use ($year) {
            $year->update(['status' => AcademicYearStatusEnum::Closed]);

            AcademicYearClosed::dispatch($year);

            return $year;
        });
    }

    public function reopen(AcademicYear $year): AcademicYear
    {
        if (! $year->isClosed()) {
            throw ValidationException::withMessages([
                'status' => "Seule une année clôturée peut être réouverte.",
            ]);
        }

        return DB::transaction(function () use ($year) {
            $this->closeOtherActiveYears($year->school_id, $year->id);

            $year->update(['status' => AcademicYearStatusEnum::Active]);

            AcademicYearActivated::dispatch($year);

            return $year;
        });
    }

    public function archive(AcademicYear $year): void
    {
        if ($year->isActive()) {
            throw ValidationException::withMessages([
                'status' => "Une année active doit d'abord être clôturée avant d'être archivée.",
            ]);
        }

        DB::transaction(function () use ($year) {
            $year->update(['status' => AcademicYearStatusEnum::Archived]);
            $year->delete();
        });
    }

    public function restore(AcademicYear $year): AcademicYear
    {
        return DB::transaction(function () use ($year) {
            $year->restore();
            $year->update(['status' => AcademicYearStatusEnum::Closed]);

            return $year->fresh();
        });
    }

    private function closeOtherActiveYears(string $schoolId, string $exceptId): void
    {
        $currentlyActive = $this->repository->findActive($schoolId);

        if ($currentlyActive && $currentlyActive->id !== $exceptId) {
            $currentlyActive->update(['status' => AcademicYearStatusEnum::Closed]);
        }
    }
}
