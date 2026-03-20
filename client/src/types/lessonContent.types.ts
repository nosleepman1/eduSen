export type LessonContentStatus = 'ACTIVE' | 'ARCHIVED';

/** Mirrors LessonContentResource. */
export interface LessonContent {
  id: string;
  lesson_session_id: string;
  title: string;
  description: string | null;
  progress_number: number | null;
  status: LessonContentStatus;
}

export interface LessonContentFormPayload {
  lesson_session_id: string;
  title: string;
  description?: string | null;
  progress_number?: number | null;
}
