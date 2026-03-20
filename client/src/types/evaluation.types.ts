export type EvaluationType = 'DEVOIR' | 'EXAMEN';
export type EvaluationStatus = 'DRAFT' | 'OPEN' | 'CLOSED' | 'LOCKED' | 'ARCHIVED';

export const EVALUATION_TYPE_LABELS: Record<EvaluationType, string> = {
  DEVOIR: 'Devoir',
  EXAMEN: 'Examen',
};

export const EVALUATION_STATUS_LABELS: Record<EvaluationStatus, string> = {
  DRAFT: 'Brouillon',
  OPEN: 'Ouverte',
  CLOSED: 'Clôturée',
  LOCKED: 'Verrouillée',
  ARCHIVED: 'Archivée',
};

/** Mirrors EvaluationResource. No relation is eager-loaded — resolve teaching_assignment_id/academic_period_id via their own lists. */
export interface Evaluation {
  id: string;
  academic_year_id: string;
  academic_period_id: string;
  teaching_assignment_id: string;
  lesson_session_id: string | null;
  type: EvaluationType;
  title: string;
  description: string | null;
  evaluation_date: string;
  max_score: number;
  coefficient: number | null;
  status: EvaluationStatus;
}

export interface EvaluationFormPayload {
  academic_period_id: string;
  teaching_assignment_id: string;
  lesson_session_id?: string | null;
  type: EvaluationType;
  title: string;
  description?: string | null;
  evaluation_date: string;
  max_score: number;
  coefficient?: number | null;
}

/** Non-blocking warning from the server: "deux Examens sur la même période" is flagged, not refused. */
export interface EvaluationCreateResult {
  evaluation: Evaluation;
  warning: string | null;
}
