/** Mirrors AcademicPeriodResource. */
export type AcademicPeriodStatus = 'DRAFT' | 'ACTIVE' | 'CLOSED' | 'ARCHIVED';

export const ACADEMIC_PERIOD_STATUS_LABELS: Record<AcademicPeriodStatus, string> = {
  DRAFT: 'Brouillon',
  ACTIVE: 'Active',
  CLOSED: 'Clôturée',
  ARCHIVED: 'Archivée',
};

export interface AcademicPeriod {
  id: string;
  academic_year_id: string;
  name: string;
  start_date: string;
  end_date: string;
  status: AcademicPeriodStatus;
  created_at: string | null;
  updated_at: string | null;
}

export interface AcademicPeriodFormPayload {
  academic_year_id: string;
  name: string;
  start_date: string;
  end_date: string;
}

/**
 * POST /academic/years/{id}/periods/generate. Gated by academic-year.manage,
 * not academic-period.manage (verified in routes/api/v1/academic.php) — it
 * writes AcademicPeriod rows but is treated as an action on the year.
 */
export type PeriodGenerationMode = 'SEMESTER' | 'TRIMESTER' | 'QUARTER';

export const GENERATION_MODE_LABELS: Record<PeriodGenerationMode, string> = {
  SEMESTER: 'Semestres (2)',
  TRIMESTER: 'Trimestres (3)',
  QUARTER: 'Quarters (4)',
};
