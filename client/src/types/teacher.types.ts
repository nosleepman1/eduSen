/**
 * Mirrors TeacherResource (server/app/Http/Resources/School/TeacherResource.php).
 * No status, no soft-delete on this entity: archive/restore delegate to the
 * linked Personnel record (server/app/Services/Api/V1/School/TeacherService.php).
 */
export interface Teacher {
  id: string;
  personnel_id: string;
  full_name: string;
  speciality: string | null;
  diploma: string | null;
  license_number: string | null;
  years_experience: number | null;
}

/**
 * POST /teachers. personnel_id must already hold an active TEACHER role in
 * this school (server-side check in TeacherService::create) — the create
 * screen restricts the picker to personnel with that role, but the server is
 * still the one that decides.
 */
export interface CreateTeacherPayload {
  personnel_id: string;
  speciality: string;
  diploma?: string | null;
  license_number?: string | null;
  years_experience?: number | null;
}

/** PUT /teachers/{id}. personnel_id is not editable — absent from the server's UpdateTeacherRequest. */
export interface UpdateTeacherPayload {
  speciality?: string;
  diploma?: string | null;
  license_number?: string | null;
  years_experience?: number | null;
}
