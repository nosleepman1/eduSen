<?php

use App\Models\AcademicYear;
use App\Models\HolidayEvent;
use App\Models\SchoolCalendar;

it('creates a school calendar for an academic year', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson('/api/v1/academic/school-calendars', [
            'academic_year_id' => $year->id,
            'name' => 'Calendrier Officiel 2026-2027',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('school_calendars', ['academic_year_id' => $year->id]);
});

it('adds a holiday event to a calendar', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    $calendar = SchoolCalendar::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/school-calendars/{$calendar->id}/holidays", [
            'name' => 'Tabaski',
            'type' => 'VARIABLE_HOLIDAY',
            'start_date' => '2027-06-05',
            'end_date' => '2027-06-07',
            'is_estimated' => true,
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('holiday_events', ['school_calendar_id' => $calendar->id, 'name' => 'Tabaski']);
});

it('refuses overlapping holiday events on the same calendar', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    $calendar = SchoolCalendar::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);

    HolidayEvent::factory()->create([
        'school_id' => $owner->test_school->id,
        'school_calendar_id' => $calendar->id,
        'start_date' => '2026-12-20',
        'end_date' => '2027-01-05',
    ]);

    $response = $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->postJson("/api/v1/academic/school-calendars/{$calendar->id}/holidays", [
            'name' => 'Vacances chevauchantes',
            'type' => 'SCHOOL_BREAK',
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-10',
        ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('HOLIDAY_EVENT_OVERLAP');
});

it('deletes a holiday event', function () {
    $owner = ownerWithActiveSubscription();
    $year = AcademicYear::factory()->create(['school_id' => $owner->test_school->id]);
    $calendar = SchoolCalendar::factory()->create(['school_id' => $owner->test_school->id, 'academic_year_id' => $year->id]);
    $holiday = HolidayEvent::factory()->create(['school_id' => $owner->test_school->id, 'school_calendar_id' => $calendar->id]);

    $this->withHeader('Authorization', 'Bearer ' . $owner->test_token)
        ->deleteJson("/api/v1/academic/school-calendars/holidays/{$holiday->id}")
        ->assertOk();

    $this->assertSoftDeleted('holiday_events', ['id' => $holiday->id]);
});

it('isolates calendars between schools', function () {
    $ownerA = ownerWithActiveSubscription();
    $ownerB = ownerWithActiveSubscription();

    $yearB = AcademicYear::factory()->create(['school_id' => $ownerB->test_school->id]);
    $calendarB = SchoolCalendar::factory()->create(['school_id' => $ownerB->test_school->id, 'academic_year_id' => $yearB->id]);

    $this->withHeader('Authorization', 'Bearer ' . $ownerA->test_token)
        ->getJson("/api/v1/academic/school-calendars/{$calendarB->id}")
        ->assertStatus(403);
});
