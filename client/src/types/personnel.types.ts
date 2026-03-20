/**
 * Mirrors PersonnelResource (server/app/Http/Resources/School/PersonnelResource.php).
 * Personnel has no creation route: it only comes into existence when an
 * invitation is accepted (server/app/Services/Api/V1/School/InvitationService.php).
 */
export type PersonnelStatus = 'ACTIVE' | 'SUSPENDED' | 'TERMINATED';

/**
 * Assignable via PATCH /personnel/{id}/roles. OWNER and SUPER_ADMIN are
 * deliberately absent — the backend's Rule::in() on that route excludes both.
 */
export type AssignableRole = 'ADMIN' | 'TEACHER' | 'SUPERVISOR' | 'ACCOUNTANT' | 'SECRETARY' | 'LIBRARIAN';

export const ASSIGNABLE_ROLES: AssignableRole[] = [
  'ADMIN',
  'TEACHER',
  'SUPERVISOR',
  'ACCOUNTANT',
  'SECRETARY',
  'LIBRARIAN',
];

export const ROLE_LABELS: Record<AssignableRole, string> = {
  ADMIN: 'Administrateur',
  TEACHER: 'Enseignant',
  SUPERVISOR: 'Surveillant',
  ACCOUNTANT: 'Comptable',
  SECRETARY: 'Secrétaire',
  LIBRARIAN: 'Bibliothécaire',
};

export const STATUS_LABELS: Record<PersonnelStatus, string> = {
  ACTIVE: 'Actif',
  SUSPENDED: 'Suspendu',
  TERMINATED: 'Terminé',
};

export interface Personnel {
  id: string;
  registration_number: string;
  full_name: string;
  email: string | null;
  department: string | null;
  position: string | null;
  hire_date: string | null;
  status: PersonnelStatus | null;
  /** Active role values in the current school; empty if none. */
  roles: string[];
}

/** PUT /personnel/{id}. All fields optional — the backend accepts partial updates. */
export interface UpdatePersonnelPayload {
  department?: string | null;
  position?: string | null;
  hire_date?: string | null;
}

/** PATCH /personnel/{id}/roles. Full replacement (sync), not a diff. */
export interface UpdatePersonnelRolesPayload {
  roles: AssignableRole[];
}
