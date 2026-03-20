/**
 * Mirrors GuardianResource outside of a student context (no pivot fields —
 * those only appear when this resource is nested under a Student's
 * `guardians`, see LinkedGuardian in student.types.ts).
 */
export interface Guardian {
  id: string;
  firstname: string;
  lastname: string;
  full_name: string;
  phone: string;
  phone_secondary: string | null;
  email: string | null;
  profession: string | null;
  address: string | null;
  national_id: string | null;
}

/**
 * POST/PUT /guardians. phone and email are unique per school — verified
 * against StoreGuardianRequest/UpdateGuardianRequest — but the uniqueness
 * check does not exclude archived (soft-deleted) guardians. Reusing an
 * archived guardian's phone number therefore fails with a 422 the form
 * cannot resolve (there is no "view archived guardians" screen to restore
 * from instead) — a real backend gap, not something this form can paper over.
 */
export interface GuardianFormPayload {
  firstname: string;
  lastname: string;
  phone: string;
  phone_secondary?: string | null;
  email?: string | null;
  profession?: string | null;
  address?: string | null;
  national_id?: string | null;
}
