<?php

use App\Models\AcademicYear;
use App\Models\HolidayEvent;
use App\Models\SchoolCalendar;

it('can create a school calendar with holiday events', function () {
    $year = AcademicYear::factory()->create();

    $calendar = SchoolCalendar::create([
        'school_id' => $year->school_id,
        'academic_year_id' => $year->id,
        'name' => 'Calendrier Officiel',
    ]);

    expect($calendar->id)->not->toBeNull();
    expect($calendar->name)->toBe('Calendrier Officiel');

    $holiday1 = HolidayEvent::create([
        'school_id' => $year->school_id,
        'school_calendar_id' => $calendar->id,
        'name' => 'Tabaski',
        'type' => 'VARIABLE_HOLIDAY',
        'start_date' => '2026-10-15',
        'end_date' => '2026-10-17',
        'is_estimated' => true,
    ]);

    $holiday2 = HolidayEvent::create([
        'school_id' => $year->school_id,
        'school_calendar_id' => $calendar->id,
        'name' => 'Fête de l\'Indépendance',
        'type' => 'FIXED_HOLIDAY',
        'start_date' => '2027-04-04',
        'end_date' => '2027-04-04',
        'is_estimated' => false,
    ]);

    expect($holiday1->id)->not->toBeNull();
    expect($holiday2->id)->not->toBeNull();
    expect($calendar->holidayEvents)->toHaveCount(2);
});

it('can scope estimated vs confirmed holiday events', function () {
    $calendar = SchoolCalendar::factory()->create();

    HolidayEvent::factory()->create([
        'school_id' => $calendar->school_id,
        'school_calendar_id' => $calendar->id,
        'name' => 'Korité',
        'type' => 'VARIABLE_HOLIDAY',
        'is_estimated' => true,
    ]);

    HolidayEvent::factory()->create([
        'school_id' => $calendar->school_id,
        'school_calendar_id' => $calendar->id,
        'name' => 'Jour de l\'An',
        'type' => 'FIXED_HOLIDAY',
        'is_estimated' => false,
    ]);

    expect(HolidayEvent::estimated()->get())->toHaveCount(1);
    expect(HolidayEvent::confirmed()->get())->toHaveCount(1);
});

it('supports soft deletes for calendars and holidays', function () {
    $calendar = SchoolCalendar::factory()->create();
    $holiday = HolidayEvent::factory()->create([
        'school_id' => $calendar->school_id,
        'school_calendar_id' => $calendar->id,
    ]);

    $holiday->delete();
    expect(HolidayEvent::find($holiday->id))->toBeNull();
    expect(HolidayEvent::withTrashed()->find($holiday->id))->not->toBeNull();

    $calendar->delete();
    expect(SchoolCalendar::find($calendar->id))->toBeNull();
    expect(SchoolCalendar::withTrashed()->find($calendar->id))->not->toBeNull();
});
