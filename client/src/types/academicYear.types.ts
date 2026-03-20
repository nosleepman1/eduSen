/** Mirrors AcademicYearResource. */
export type AcademicYearStatus = 'DRAFT' | 'ACTIVE' | 'CLOSED' | 'ARCHIVED';

export const ACADEMIC_YEAR_STATUS_LABELS: Record<AcademicYearStatus, string> = {
  DRAFT: 'Brouillon',
  ACTIVE: 'Active',
  CLOSED: 'Clôturée',
  ARCHIVED: 'Archivée',
};

export interface AcademicYear {
  id: string;
  name: string;
  start_date: string;
  end_date: string;
  status: AcademicYearStatus;
  created_at: string | null;
  updated_at: string | null;
}

export interface AcademicYearFormPayload {
  name: string;
  start_date: string;
  end_date: string;
}
