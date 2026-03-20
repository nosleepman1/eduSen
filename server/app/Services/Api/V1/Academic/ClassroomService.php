<?php

namespace App\Services\Api\V1\Academic;

use App\Enums\ClassroomStatusEnum;
use App\Exceptions\Academic\ClassroomEndDateOutOfRangeException;
use App\Exceptions\Academic\ClassroomNameConflictException;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Level;
use App\Models\Personnel;
use App\Models\Specialization;
use App\Repositories\Api\V1\Academic\ClassroomRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassroomService
{
    public function __construct(
        private readonly ClassroomRepository $repository
    ) {}

    public function create(array $data, Level $level): Classroom
    {
        $year = $level->academicYear;

        $this->assertEndDateWithinYear($year, $data['end_date_override'] ?? null);

        return DB::transaction(function () use ($data, $level, $year) {
            $specializationId = $data['specialization_id'] ?? null;

            if ($this->repository->existsForCombination($year->id, $level->id, $specializationId, $data['name'])) {
                throw new ClassroomNameConflictException();
            }

            $code = $data['code'] ?? null;
            $code = ($code === null || $code === '') ? $this->generateCode($level, $specializationId, $data['name']) : $code;

            $classroom = Classroom::create([
                'school_id' => $level->school_id,
                'academic_year_id' => $year->id,
                'level_id' => $level->id,
                'specialization_id' => $specializationId,
                'name' => $data['name'],
                'code' => $code,
                'capacity' => $data['capacity'],
                'main_room' => $data['main_room'] ?? null,
                'description' => $data['description'] ?? null,
                'end_date_override' => $data['end_date_override'] ?? null,
                'status' => ClassroomStatusEnum::Draft,
            ]);

            return $classroom->setRelation('level', $level)->loadMissing('specialization');
        });
    }

    public function update(Classroom $classroom, array $data): Classroom
    {
        if (array_key_exists('end_date_override', $data)) {
            $this->assertEndDateWithinYear($classroom->academicYear, $data['end_date_override']);
        }

        $name = $data['name'] ?? $classroom->name;
        $specializationId = array_key_exists('specialization_id', $data) ? $data['specialization_id'] : $classroom->specialization_id;

        return DB::transaction(function () use ($classroom, $data, $name, $specializationId) {
            if ($this->repository->existsForCombination(
                $classroom->academic_year_id, $classroom->level_id, $specializationId, $name, $classroom->id
            )) {
                throw new ClassroomNameConflictException();
            }

            $classroom->update($data);

            return $classroom->loadMissing(['level', 'specialization']);
        });
    }

    public function open(Classroom $classroom): Classroom
    {
        $classroom->update(['status' => ClassroomStatusEnum::Active]);

        return $classroom;
    }

    public function close(Classroom $classroom): Classroom
    {
        $classroom->update(['status' => ClassroomStatusEnum::Closed]);

        return $classroom;
    }

    public function archive(Classroom $classroom): void
    {
        DB::transaction(function () use ($classroom) {
            $classroom->update(['status' => ClassroomStatusEnum::Archived]);
            $classroom->delete();
        });
    }

    public function restore(Classroom $classroom): Classroom
    {
        return DB::transaction(function () use ($classroom) {
            $classroom->restore();
            $classroom->update(['status' => ClassroomStatusEnum::Closed]);

            return $classroom;
        });
    }

    public function assignPrincipalTeacher(Classroom $classroom, ?Personnel $personnel): Classroom
    {
        if ($personnel !== null) {
            if (! $personnel->isActive() || ! $personnel->teacher()->exists()) {
                throw ValidationException::withMessages([
                    'main_teacher_id' => "Le professeur principal doit être un enseignant actif.",
                ]);
            }
        }

        $classroom->update(['main_teacher_id' => $personnel?->id]);

        return $classroom;
    }

    private function generateCode(Level $level, ?string $specializationId, string $name): string
    {
        $specializationCode = $specializationId ? Specialization::find($specializationId)?->code : null;

        $segments = array_filter([$level->code, $specializationCode, $name], fn ($segment) => $segment !== null && $segment !== '');

        return Str::upper(Str::slug(implode('-', $segments), '-'));
    }

    private function assertEndDateWithinYear(AcademicYear $year, ?string $endDateOverride): void
    {
        if ($endDateOverride === null) {
            return;
        }

        $endDateOverride = Carbon::parse($endDateOverride);

        if ($endDateOverride->lt($year->start_date) || $endDateOverride->gt($year->end_date)) {
            throw new ClassroomEndDateOutOfRangeException();
        }
    }
}
