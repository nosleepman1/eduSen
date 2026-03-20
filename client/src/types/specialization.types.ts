/**
 * Mirrors SpecializationResource. Simplest Academic Management domain: no
 * status, no state machine, just archive/restore. index() also returns every
 * record via ->get() rather than ->paginate() — there is no `meta` to read,
 * the screen has no pagination controls to match.
 */
export interface Specialization {
  id: string;
  name: string;
  code: string;
  description: string | null;
}

export interface SpecializationFormPayload {
  name: string;
  code: string;
  description?: string | null;
}
