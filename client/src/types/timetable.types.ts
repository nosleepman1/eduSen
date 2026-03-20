export type TimetableStatus = 'DRAFT' | 'ACTIVE' | 'ARCHIVED';

export const TIMETABLE_STATUS_LABELS: Record<TimetableStatus, string> = {
  DRAFT: 'Brouillon',
  ACTIVE: 'Actif',
  ARCHIVED: 'Archivé',
};

/** Mirrors TimetableResource. `classroom` is always eager-loaded server-side. */
export interface Timetable {
  id: string;
  academic_year_id: string;
  classroom_id: string;
  name: string;
  description: string | null;
  status: TimetableStatus;
  classroom?: { id: string; name: string };
}

export interface TimetableFormPayload {
  classroom_id: string;
  name: string;
  description?: string | null;
}
