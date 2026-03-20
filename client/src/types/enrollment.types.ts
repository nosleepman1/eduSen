export type EnrollmentStatus = 'ACTIVE' | 'TRANSFERRED' | 'WITHDRAWN' | 'ARCHIVED';

export const ENROLLMENT_STATUS_LABELS: Record<EnrollmentStatus, string> = {
  ACTIVE: 'Active',
  TRANSFERRED: 'Transférée',
  WITHDRAWN: 'Clôturée',
  ARCHIVED: 'Archivée',
};

/**
 * Mirrors EnrollmentResource. `classroom`/`student` are only present because
 * EnrollmentController::index()/show() always eager-load them (unlike
 * Classroom's main_teacher_id, which never comes with a name attached) — so
 * the list page can render both without a separate lookup.
 */
export interface Enrollment {
  id: string;
  academic_year_id: string;
  classroom_id: string;
  student_id: string;
  registration_number: string;
  enrolled_at: string;
  ended_at: string | null;
  status: EnrollmentStatus;
  archive_reason: string | null;
  classroom?: { id: string; name: string };
  student?: { id: string; full_name: string; student_number: string };
}

export interface EnrollmentFormPayload {
  classroom_id: string;
  student_id: string;
  enrolled_at?: string | null;
}

/** PATCH /enrollments/{id}/transfer — the only field the endpoint accepts. */
export interface TransferPayload {
  classroom_id: string;
}

/** PATCH /enrollments/{id}/withdraw — reason is optional server-side. */
export interface WithdrawPayload {
  reason?: string | null;
}
