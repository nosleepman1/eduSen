export type BulletinStatus = 'DRAFT' | 'VALIDATED' | 'PUBLISHED' | 'LOCKED' | 'ARCHIVED';

export const BULLETIN_STATUS_LABELS: Record<BulletinStatus, string> = {
  DRAFT: 'Brouillon',
  VALIDATED: 'Validé',
  PUBLISHED: 'Publié',
  LOCKED: 'Verrouillé',
  ARCHIVED: 'Archivé',
};

export interface BulletinLine {
  id: string;
  curriculum_id: string;
  subject_average: number | null;
  coefficient: number;
  points: number | null;
  teacher_comment: string | null;
}

/** Mirrors BulletinResource. No relation is eager-loaded on enrollment_id/academic_period_id — resolve via their own lists. */
export interface Bulletin {
  id: string;
  academic_period_id: string;
  enrollment_id: string;
  general_average: number | null;
  class_rank: number | null;
  total_students: number | null;
  mention: string | null;
  class_council_comment: string | null;
  status: BulletinStatus;
  generated_at: string | null;
  published_at: string | null;
  lines: BulletinLine[];
}

export interface GenerateBulletinPayload {
  enrollment_id: string;
  academic_period_id: string;
}

export interface GenerateClassBulletinsPayload {
  classroom_id: string;
  academic_period_id: string;
}
