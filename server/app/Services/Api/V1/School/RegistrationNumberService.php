<?php

namespace App\Services\Api\V1\School;

use App\Models\RegistrationSequence;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class RegistrationNumberService
{
    /**
     * Generate a thread-safe sequential registration number for a specific type.
     * Format: {SCHOOL_CODE}-{YEAR}-{PREFIX}-{SEQUENCE}
     * Example: SCH-2024-0001-2024-STD-000001
     */
    public function generate(string $schoolId, string $type): string
    {
        return DB::transaction(function () use ($schoolId, $type) {
            $school = School::findOrFail($schoolId);
            $year = (int) now()->format('Y');
            $prefix = $this->typePrefix($type);

            // Lock the row to prevent race conditions during generation
            $sequence = RegistrationSequence::lockForUpdate()->firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'type' => $type,
                    'year' => $year,
                ],
                [
                    'last_number' => 0,
                ]
            );

            // Increment and save
            $sequence->last_number++;
            $sequence->save();

            // Format: CODE-YEAR-PREFIX-00000X
            return sprintf(
                '%s-%d-%s-%06d',
                $school->code,
                $year,
                $prefix,
                $sequence->last_number
            );
        });
    }

    private function typePrefix(string $type): string
    {
        return match (strtoupper($type)) {
            'STUDENT' => 'STD',
            'EMPLOYEE' => 'EMP',
            'PAYMENT' => 'PAY',
            'INVOICE' => 'INV',
            'ENROLLMENT' => 'ENR',
            default => 'GEN', // Generic fallback
        };
    }
}
