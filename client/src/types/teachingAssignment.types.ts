export type TeachingAssignmentRole = 'PRINCIPAL' | 'SUBSTITUTE' | 'CO_TEACHER';
export type TeachingAssignmentStatus = 'ACTIVE' | 'CLOSED';

export const ROLE_LABELS: Record<TeachingAssignmentRole, string> = {
  PRINCIPAL: 'Titulaire',
  SUBSTITUTE: 'Remplaçant',
  CO_TEACHER: 'Co-enseignant',
};

export const TEACHING_ASSIGNMENT_STATUS_LABELS: Record<TeachingAssignmentStatus, string> = {
  ACTIVE: 'Active',
  CLOSED: 'Clôturée',
};

/**
 * Mirrors TeachingAssignmentResource. `classroom`/`teacher` are always
 * eager-loaded server-side; `curriculum_id` is not resolved to a subject
 * name here — cross-reference against /academic/curriculums + /subjects,
 * same pattern CurriculumPage itself uses for subject_id.
 */
export interface TeachingAssignment {
  id: string;
  academic_year_id: string;
  classroom_id: string;
  curriculum_id: string;
  teacher_id: string;
  role: TeachingAssignmentRole;
  started_at: string;
  ended_at: string | null;
  status: TeachingAssignmentStatus;
  classroom?: { id: string; name: string };
  teacher?: { id: string; full_name: string };
}

export interface TeachingAssignmentFormPayload {
  classroom_id: string;
  curriculum_id: string;
  teacher_id: string;
  role?: TeachingAssignmentRole;
  started_at?: string | null;
}

export interface ReassignPayload {
  teacher_id: string;
}
