export type DayOfWeek = 'MONDAY' | 'TUESDAY' | 'WEDNESDAY' | 'THURSDAY' | 'FRIDAY' | 'SATURDAY' | 'SUNDAY';
export type ScheduleEntryStatus = 'ACTIVE' | 'ARCHIVED';

export const DAY_LABELS: Record<DayOfWeek, string> = {
  MONDAY: 'Lundi',
  TUESDAY: 'Mardi',
  WEDNESDAY: 'Mercredi',
  THURSDAY: 'Jeudi',
  FRIDAY: 'Vendredi',
  SATURDAY: 'Samedi',
  SUNDAY: 'Dimanche',
};

export const SCHEDULE_ENTRY_STATUS_LABELS: Record<ScheduleEntryStatus, string> = {
  ACTIVE: 'Actif',
  ARCHIVED: 'Archivé',
};

/**
 * Mirrors ScheduleEntryResource. `teaching_assignment` only exposes
 * teacher_id/curriculum_id (no names) — resolve via the teacher and
 * curriculum/subject lists, same cross-referencing pattern used on the
 * TeachingAssignment screen itself.
 */
export interface ScheduleEntry {
  id: string;
  timetable_id: string;
  teaching_assignment_id: string;
  day_of_week: DayOfWeek;
  start_time: string;
  end_time: string;
  duration_minutes: number;
  room: string | null;
  frequency: 'WEEKLY';
  status: ScheduleEntryStatus;
  teaching_assignment?: { id: string; teacher_id: string; curriculum_id: string };
}

export interface ScheduleEntryFormPayload {
  timetable_id: string;
  teaching_assignment_id: string;
  day_of_week: DayOfWeek;
  start_time: string;
  end_time: string;
  room?: string | null;
}

/** Non-blocking warning from the server when hours don't match the curriculum's weekly_hours. */
export interface ScheduleEntryCreateResult {
  entry: ScheduleEntry;
  volume_warning: string | null;
}

export interface GenerateSessionsResult {
  created: unknown[];
  skipped_holiday: number;
  skipped_existing: number;
}
