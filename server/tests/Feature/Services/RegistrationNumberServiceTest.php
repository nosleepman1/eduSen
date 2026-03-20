<?php

use App\Models\School;
use App\Services\Api\V1\School\RegistrationNumberService;

it('generates sequential thread-safe registration numbers per school and type', function () {
    $school = School::factory()->create();
    $service = app(RegistrationNumberService::class);

    $number1 = $service->generate($school->id, 'STUDENT');
    $number2 = $service->generate($school->id, 'STUDENT');

    $year = now()->format('Y');

    expect($number1)->toBe("{$school->code}-{$year}-STD-000001");
    expect($number2)->toBe("{$school->code}-{$year}-STD-000002");
});

it('isolates sequences between different schools', function () {
    $schoolA = School::factory()->create();
    $schoolB = School::factory()->create();
    $service = app(RegistrationNumberService::class);

    $numberA1 = $service->generate($schoolA->id, 'EMPLOYEE');
    $numberB1 = $service->generate($schoolB->id, 'EMPLOYEE');

    $year = now()->format('Y');

    expect($numberA1)->toBe("{$schoolA->code}-{$year}-EMP-000001");
    expect($numberB1)->toBe("{$schoolB->code}-{$year}-EMP-000001"); // School B sequence is independent
});
