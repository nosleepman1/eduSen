export type LessonSessionStatus =
  | 'SCHEDULED'
  | 'PENDING_START'
  | 'IN_PROGRESS'
  | 'PENDING_VALIDATION'
  | 'COMPLETED'
  | 'CANCELLED'
  | 'POSTPONED'
  | 'ARCHIVED';

export const LESSON_SESSION_STATUS_LABELS: Record<LessonSessionStatus, string> = {
  SCHEDULED: 'Planifiée',
  PENDING_START: 'En attente de démarrage',
  IN_PROGRESS: 'En cours',
  PENDING_VALIDATION: 'En attente de validation',
  COMPLETED: 'Terminée',
  CANCELLED: 'Annulée',
  POSTPONED: 'Reportée',
  ARCHIVED: 'Archivée',
};

/**
 * Mirrors LessonSessionResource. Status here can be stale by up to one
 * request: the server only advances Scheduled -> PendingStart ->
 * PendingValidation lazily on read (LessonSessionService::tick), there is
 * no background scheduler — refetching the list is what re-syncs it.
 */
export interface LessonSession {
  id: string;
  schedule_entry_id: string;
  session_date: string;
  scheduled_start_time: string;
  scheduled_end_time: string;
  actual_start: string | null;
  actual_end: string | null;
  status: LessonSessionStatus;
  cancellation_reason: string | null;
  admin_comment: string | null;
  schedule_entry?: { id: string; teaching_assignment_id: string };
}

export interface CancelPayload {
  reason: string;
  comment?: string | null;
}

export interface PostponePayload {
  comment?: string | null;
}

export type ValidateDecision = 'COMPLETED' | 'CANCELLED' | 'POSTPONED';

export interface ValidatePayload {
  decision: ValidateDecision;
  reason?: string | null;
  comment?: string | null;
}
