<?php

namespace Database\Factories;

use App\Models\HolidayEvent;
use App\Models\SchoolCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

class HolidayEventFactory extends Factory
{
    protected $model = HolidayEvent::class;

    public function definition(): array
    {
        $calendar = SchoolCalendar::factory()->create();
        $type = fake()->randomElement(['FIXED_HOLIDAY', 'VARIABLE_HOLIDAY', 'SCHOOL_BREAK']);
        $start = fake()->dateTimeBetween('2026-10-01', '2027-06-30')->format('Y-m-d');
        $end = fake()->dateTimeBetween($start, '2027-07-31')->format('Y-m-d');

        return [
            'school_id' => $calendar->school_id,
            'school_calendar_id' => $calendar->id,
            'name' => fake()->randomElement(['Tabaski', 'Korité', 'Noël', 'Pâques', 'Ascension', 'Fête du Travail']),
            'type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'is_estimated' => $type === 'VARIABLE_HOLIDAY',
        ];
    }
}
