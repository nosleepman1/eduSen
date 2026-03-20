/** Mirrors CurriculumResource. Each row is one "subject taught in this level, with this many hours/coefficient". */
export interface Curriculum {
  id: string;
  academic_year_id: string;
  level_id: string;
  specialization_id: string | null;
  subject_id: string;
  coefficient: number;
  weekly_hours: number;
  display_order: number | null;
}

export interface CurriculumFormPayload {
  level_id: string;
  specialization_id?: string | null;
  subject_id: string;
  coefficient: number;
  weekly_hours: number;
}

export interface DuplicateCurriculumPayload {
  from_academic_year_id: string;
  to_academic_year_id: string;
}

/**
 * CurriculumService::duplicate() maps levels by code (stable across years)
 * and skips a line rather than failing the whole batch when it can't be
 * placed — these are the three reasons it reports, verbatim from the server.
 */
export type SkipReason = 'source_level_archived' | 'no_matching_level' | 'already_exists';

export const SKIP_REASON_LABELS: Record<SkipReason, string> = {
  source_level_archived: "Le niveau d'origine a été archivé",
  no_matching_level: "Aucun niveau de même code dans l'année cible",
  already_exists: 'Existe déjà dans l\'année cible',
};

export interface SkippedLine {
  curriculum_id: string;
  reason: SkipReason;
}

export interface DuplicateCurriculumResult {
  created: Curriculum[];
  skipped: SkippedLine[];
}
