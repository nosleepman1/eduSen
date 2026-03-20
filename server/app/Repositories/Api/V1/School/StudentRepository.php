<?php

namespace App\Repositories\Api\V1\School;

use App\Enums\StudentStatusEnum;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentRepository
{
    public function countActive(string $schoolId): int
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->where('status', StudentStatusEnum::Active)
            ->count();
    }

    public function paginateForSchool(string $schoolId, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Student::query()
            ->where('school_id', $schoolId)
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%");
            }))
            ->orderBy('lastname')
            ->paginate($perPage);
    }
}
