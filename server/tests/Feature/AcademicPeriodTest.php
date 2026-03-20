<?php

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\QueryException;

it('can create an academic period', function () {
    $year = AcademicYear::factory()->create();
    $period = AcademicPeriod::create([
        'school_id' => $year->school_id,
        'academic_year_id' => $year->id,
        'name' => 'Semestre 1',
        'start_date' => '2026-10-01',
        'end_date' => '2027-02-15',
        'status' => 'DRAFT',
    ]);

    expect($period->id)->not->toBeNull();
    expect($period->name)->toBe('Semestre 1');
    expect($period->status->value)->toBe('DRAFT');
});

it('enforces uniqueness of name per academic year', function () {
    $year = AcademicYear::factory()->create();

    AcademicPeriod::create([
        'school_id' => $year->school_id,
        'academic_year_id' => $year->id,
        'name' => 'Semestre 1',
        'start_date' => '2026-10-01',
        'end_date' => '2027-02-15',
    ]);

    expect(fn () => AcademicPeriod::create([
        'school_id' => $year->school_id,
        'academic_year_id' => $year->id,
        'name' => 'Semestre 1',
        'start_date' => '2027-02-16',
        'end_date' => '2027-07-31',
    ]))->toThrow(QueryException::class);
});

it('allows same period name in different academic years', function () {
    $school = School::factory()->create();
    $year1 = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026 - 2027']);
    $year2 = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2027 - 2028']);

    $period1 = AcademicPeriod::create([
        'school_id' => $school->id,
        'academic_year_id' => $year1->id,
        'name' => 'Semestre 1',
        'start_date' => '2026-10-01',
        'end_date' => '2027-02-15',
    ]);

    $period2 = AcademicPeriod::create([
        'school_id' => $school->id,
        'academic_year_id' => $year2->id,
        'name' => 'Semestre 1',
        'start_date' => '2027-10-01',
        'end_date' => '2028-02-15',
    ]);

    expect($period1->id)->not->toBeNull();
    expect($period2->id)->not->toBeNull();
    expect($period1->id)->not->toBe($period2->id);
});

it('can scope active periods', function () {
    $year = AcademicYear::factory()->create();
    $period1 = AcademicPeriod::factory()->create([
        'school_id' => $year->school_id,
        'academic_year_id' => $year->id,
        'status' => 'ACTIVE',
    ]);
    $period2 = AcademicPeriod::factory()->create([
        'school_id' => $year->school_id,
        'academic_year_id' => $year->id,
        'status' => 'DRAFT',
    ]);

    $activePeriods = AcademicPeriod::active()->get();

    expect($activePeriods)->toHaveCount(1);
    expect($activePeriods->first()->id)->toBe($period1->id);
});
