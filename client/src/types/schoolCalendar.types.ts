export type HolidayType = 'FIXED_HOLIDAY' | 'VARIABLE_HOLIDAY' | 'SCHOOL_BREAK';

export const HOLIDAY_TYPE_LABELS: Record<HolidayType, string> = {
  FIXED_HOLIDAY: 'Jour férié fixe',
  VARIABLE_HOLIDAY: 'Jour férié mobile',
  SCHOOL_BREAK: 'Vacances scolaires',
};

export interface HolidayEvent {
  id: string;
  school_calendar_id: string;
  name: string;
  type: HolidayType;
  start_date: string;
  end_date: string;
  is_estimated: boolean;
}

/**
 * Mirrors SchoolCalendarResource. `holiday_events` is absent (not an empty
 * array) unless the server loaded it — only GET /school-calendars/{id} does;
 * the index list never does, to avoid N+1 across every calendar.
 */
export interface SchoolCalendar {
  id: string;
  academic_year_id: string;
  name: string;
  created_at: string | null;
  holiday_events?: HolidayEvent[];
}

/** POST /school-calendars. No update/destroy route exists for the calendar itself. */
export interface CreateSchoolCalendarPayload {
  academic_year_id: string;
  name: string;
}

export interface HolidayEventFormPayload {
  name: string;
  type: HolidayType;
  start_date: string;
  end_date: string;
  is_estimated?: boolean;
}
