<?php

namespace App\Enums;

/**
 * Only Weekly is implemented today. EveryTwoWeeks/Monthly/Custom are called
 * out in the spec as future evolutions — the DB enum only lists WEEKLY, so
 * adding a real case here later means a migration to widen it too (same
 * shape as the registration_sequences.type widening for Enrollment).
 */
enum ScheduleEntryFrequencyEnum: string
{
    case Weekly = 'WEEKLY';
}
