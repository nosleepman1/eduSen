<?php

use App\Models\School;
use App\Models\Specialization;
use Illuminate\Database\QueryException;

it('can create a specialization', function () {
    $school = School::factory()->create();
    $specialization = Specialization::create([
        'school_id' => $school->id,
        'name' => 'Scientifique S1',
        'code' => 'S1',
        'description' => 'Filière Scientifique Avancée',
    ]);

    expect($specialization->id)->not->toBeNull();
    expect($specialization->name)->toBe('Scientifique S1');
    expect($specialization->code)->toBe('S1');
    expect($specialization->description)->toBe('Filière Scientifique Avancée');
});

it('enforces uniqueness of code per school', function () {
    $school = School::factory()->create();
    Specialization::create([
        'school_id' => $school->id,
        'name' => 'Scientifique S1',
        'code' => 'S1',
    ]);

    expect(fn () => Specialization::create([
        'school_id' => $school->id,
        'name' => 'Scientifique S1 Bis',
        'code' => 'S1',
    ]))->toThrow(QueryException::class);
});

it('allows different schools to have the same specialization code', function () {
    $school1 = School::factory()->create();
    $school2 = School::factory()->create();

    $spec1 = Specialization::create([
        'school_id' => $school1->id,
        'name' => 'Série S1',
        'code' => 'S1',
    ]);

    $spec2 = Specialization::create([
        'school_id' => $school2->id,
        'name' => 'Série S1',
        'code' => 'S1',
    ]);

    expect($spec1->id)->not->toBeNull();
    expect($spec2->id)->not->toBeNull();
    expect($spec1->id)->not->toBe($spec2->id);
});

it('supports soft deletes', function () {
    $school = School::factory()->create();
    $spec = Specialization::create([
        'school_id' => $school->id,
        'name' => 'Série G',
        'code' => 'G',
    ]);

    $spec->delete();

    expect(Specialization::find($spec->id))->toBeNull();
    expect(Specialization::withTrashed()->find($spec->id))->not->toBeNull();
});
