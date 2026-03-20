export type GradeStatus = 'PENDING' | 'GRADED' | 'ABSENT' | 'DISPENSED';

export const GRADE_STATUS_LABELS: Record<GradeStatus, string> = {
  PENDING: 'En attente',
  GRADED: 'Notée',
  ABSENT: 'Absent',
  DISPENSED: 'Dispensé',
};

/** Mirrors GradeResource. No relation eager-loaded — resolve enrollment_id via the enrollment list. */
export interface Grade {
  id: string;
  evaluation_id: string;
  enrollment_id: string;
  value: number | null;
  status: GradeStatus;
  comment: string | null;
}

/** PUT /grades/{id}. value is ignored server-side for Absent (forced to 0) and Dispensed (forced to null). */
export interface UpdateGradePayload {
  status?: GradeStatus;
  value?: number | null;
  comment?: string | null;
}

export interface SubjectAverage {
  teaching_assignment_id: string;
  curriculum_id: string;
  average: number | null;
  coefficient: number;
}

export interface AveragesResult {
  subjects: SubjectAverage[];
  general_average: number | null;
  has_pending: boolean;
}
