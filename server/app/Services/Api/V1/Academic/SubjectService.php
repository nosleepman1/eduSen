<?php

namespace App\Services\Api\V1\Academic;

use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class SubjectService
{
    public function create(array $data, string $schoolId): Subject
    {
        return DB::transaction(function () use ($data, $schoolId) {
            return Subject::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
            ]);
        });
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($data);

        return $subject;
    }

    public function archive(Subject $subject): void
    {
        $subject->delete();
    }

    public function restore(Subject $subject): Subject
    {
        $subject->restore();

        return $subject;
    }
}
