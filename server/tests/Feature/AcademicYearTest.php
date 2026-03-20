<?php

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\QueryException;

it('can create an academic year', function () {
    $school = School::factory()->create();
    $year = AcademicYear::create([
        'school_id' => $school->id,
        'name' => '2026 - 2027',
        'start_date' => '2026-10-01',
        'end_date' => '2027-07-31',
        'status' => 'DRAFT',
    ]);

    expect($year->id)->not->toBeNull();
    expect($year->name)->toBe('2026 - 2027');
    expect($year->status->value)->toBe('DRAFT');
});

it('enforces uniqueness of name per school', function () {
    $school = School::factory()->create();
    AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2026 - 2027',
    ]);

    expect(fn () => AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2026 - 2027',
    ]))->toThrow(QueryException::class);
});

it('allows different schools to have the same academic year name', function () {
    $school1 = School::factory()->create();
    $school2 = School::factory()->create();

    $year1 = AcademicYear::factory()->create([
        'school_id' => $school1->id,
        'name' => '2026 - 2027',
    ]);

    $year2 = AcademicYear::factory()->create([
        'school_id' => $school2->id,
        'name' => '2026 - 2027',
    ]);

    expect($year1->id)->not->toBeNull();
    expect($year2->id)->not->toBeNull();
    expect($year1->id)->not->toBe($year2->id);
});

it('can filter active years using scopeActive', function () {
    $school = School::factory()->create();
    $year1 = AcademicYear::factory()->create(['school_id' => $school->id, 'status' => 'ACTIVE']);
    $year2 = AcademicYear::factory()->create(['school_id' => $school->id, 'status' => 'DRAFT']);

    $activeYears = AcademicYear::active()->get();

    expect($activeYears)->toHaveCount(1);
    expect($activeYears->first()->id)->toBe($year1->id);
});
