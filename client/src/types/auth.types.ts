export type RoleEnum =
  | 'SUPER_ADMIN'
  | 'OWNER'
  | 'ADMIN'
  | 'TEACHER'
  | 'SUPERVISOR'
  | 'ACCOUNTANT'
  | 'SECRETARY'
  | 'LIBRARIAN';

export interface User {
  id: string;
  firstname: string;
  lastname: string;
  email: string;
  phone_number: string | null;
  birth_date: string | null;
  birth_place: string | null;
  gender: string | null;
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
  password_changed_at: string | null;
  last_login_at: string | null;
  email_verified_at: string | null;
}

export interface School {
  id: string;
  name: string;
  code: string;
  ninea: string;
  email: string;
  is_active: boolean;
  is_school_setup: boolean;
  setup_step: number;
  setup_completed_at: string | null;
}

export interface SchoolChoice {
  id: string;
  name: string;
  code: string;
  role: RoleEnum | null;
}

/**
 * Effective permissions of the signed-in user in the active school, as resolved
 * server-side by PermissionService. `['*']` means every permission: OWNER and
 * SUPER_ADMIN bypass permission checks instead of holding an enumerated set.
 * Read it through `can()` rather than testing membership directly.
 */
export type Permission = string;

export const ALL_PERMISSIONS = '*';

/**
 * Tolerates a missing list: the auth store is persisted to localStorage, so a
 * session opened before permissions existed rehydrates without the key. Absent
 * permissions grant nothing rather than throwing.
 */
export function can(permissions: Permission[] | null | undefined, permission: Permission): boolean {
  if (!permissions) {
    return false;
  }

  return permissions.includes(ALL_PERMISSIONS) || permissions.includes(permission);
}

interface LoginResponseBase {
  token: string;
  user: User;
  mustChangePassword: boolean;
}

export interface SuperAdminLoginResponse extends LoginResponseBase {
  type: 'super_admin';
  permissions: Permission[];
}

export interface SingleSchoolLoginResponse extends LoginResponseBase {
  type: 'single_school';
  school: School;
  role: RoleEnum | null;
  schoolSetupRequired: boolean;
  permissions: Permission[];
}

export interface MultiSchoolLoginResponse extends LoginResponseBase {
  type: 'multi_school';
  schools: SchoolChoice[];
}

export type LoginResponse =
  | SuperAdminLoginResponse
  | SingleSchoolLoginResponse
  | MultiSchoolLoginResponse;

export interface SelectSchoolResponse {
  token: string;
  user: User;
  school: School;
  role: RoleEnum | null;
  schoolSetupRequired: boolean;
  permissions: Permission[];
}

export interface AuthState {
  user: User | null;
  token: string | null;
  school: School | null;
  role: RoleEnum | null;
  permissions: Permission[];
  mustChangePassword: boolean;
  schoolSetupRequired: boolean;
  setAuth: (params: {
    user: User;
    token: string;
    school: School;
    role: RoleEnum | null;
    permissions: Permission[];
    mustChangePassword: boolean;
    schoolSetupRequired: boolean;
  }) => void;
  clearAuth: () => void;
  setSchoolSetupComplete: () => void;
}
