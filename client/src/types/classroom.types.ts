export type ClassroomStatus = 'DRAFT' | 'ACTIVE' | 'CLOSED' | 'ARCHIVED';

export const CLASSROOM_STATUS_LABELS: Record<ClassroomStatus, string> = {
  DRAFT: 'Brouillon',
  ACTIVE: 'Active',
  CLOSED: 'Fermée',
  ARCHIVED: 'Archivée',
};

/**
 * Mirrors ClassroomResource. `full_name` is a server-computed accessor
 * (level name + specialization code + name) — display it instead of `name`
 * alone. `main_teacher_id` has no accompanying name in this resource (no
 * eager-loaded relation exposed) — resolving it to a person requires
 * cross-referencing the teacher list separately, which is what
 * AssignTeacherDialog does; the main list just shows assigned/unassigned.
 */
export interface Classroom {
  id: string;
  academic_year_id: string;
  level_id: string;
  specialization_id: string | null;
  name: string;
  full_name: string;
  code: string;
  capacity: number;
  main_teacher_id: string | null;
  main_room: string | null;
  status: ClassroomStatus;
  description: string | null;
  end_date_override: string | null;
}

/**
 * `code` is optional: ClassroomService::create() generates one from
 * level.code + specialization.code + name when omitted — the form leaves it
 * blank by default rather than asking the user to invent one.
 */
export interface ClassroomFormPayload {
  level_id: string;
  specialization_id?: string | null;
  name: string;
  code?: string | null;
  capacity: number;
  main_room?: string | null;
  description?: string | null;
  end_date_override?: string | null;
}
