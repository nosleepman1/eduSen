<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Bulletin;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulletinFactory extends Factory
{
    protected $model = Bulletin::class;

    public function definition(): array
    {
        $enrollment = Enrollment::factory()->create();
        $period = AcademicPeriod::factory()->create([
            'school_id' => $enrollment->school_id,
            'academic_year_id' => $enrollment->academic_year_id,
        ]);

        return [
            'school_id' => $enrollment->school_id,
            'academic_period_id' => $period->id,
            'enrollment_id' => $enrollment->id,
            'general_average' => 14.5,
            'status' => 'DRAFT',
            'generated_at' => now(),
        ];
    }
}
