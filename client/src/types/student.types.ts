/** Mirrors StudentResource. Only MALE/FEMALE are accepted — the backend's
 *  GenderEnum also has OTHER, but StoreStudentRequest restricts to these two. */
export type StudentGender = 'MALE' | 'FEMALE';

/** Read-only: no endpoint changes a student's status other than archive/restore. */
export type StudentStatus = 'ACTIVE' | 'TRANSFERRED' | 'GRADUATED' | 'EXPELLED';

export type GuardianRelationship = 'FATHER' | 'MOTHER' | 'GUARDIAN' | 'TUTOR' | 'OTHER';

export const RELATIONSHIP_LABELS: Record<GuardianRelationship, string> = {
  FATHER: 'Père',
  MOTHER: 'Mère',
  GUARDIAN: 'Tuteur légal',
  TUTOR: 'Tuteur',
  OTHER: 'Autre',
};

export const STUDENT_STATUS_LABELS: Record<StudentStatus, string> = {
  ACTIVE: 'Actif',
  TRANSFERRED: 'Transféré',
  GRADUATED: 'Diplômé',
  EXPELLED: 'Exclu',
};

/**
 * The `guardians` key is absent (not an empty array) unless the server loaded
 * it — only GET /students/{id} does; the list endpoint never does, to avoid
 * N+1 across a page.
 */
export interface LinkedGuardian {
  id: string;
  full_name: string;
  phone: string;
  email: string | null;
  relationship: GuardianRelationship;
  is_primary_contact: boolean;
}

export interface Student {
  id: string;
  student_number: string;
  firstname: string;
  lastname: string;
  full_name: string;
  birth_date: string | null;
  birth_place: string | null;
  gender: StudentGender | null;
  nationality: string | null;
  blood_group: string | null;
  medical_info: string | null;
  address: string | null;
  status: StudentStatus | null;
  enrolled_at: string | null;
  guardians?: LinkedGuardian[];
}

export interface StudentFormPayload {
  firstname: string;
  lastname: string;
  birth_date?: string | null;
  birth_place?: string | null;
  gender?: StudentGender | null;
  nationality?: string | null;
  blood_group?: string | null;
  medical_info?: string | null;
  address?: string | null;
}

/**
 * POST /students/{id}/guardians. Not a detach — there is no unlink endpoint.
 * Submitting again for the same guardian_id updates their relationship /
 * primary-contact flag rather than creating a duplicate (syncWithoutDetaching).
 */
export interface LinkGuardianPayload {
  guardian_id: string;
  relationship: GuardianRelationship;
  is_primary?: boolean;
}
