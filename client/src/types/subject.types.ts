/**
 * Mirrors SubjectResource. Same shape as Specialization: no pagination
 * (SubjectController@index uses ->get()), no state machine, just archive/restore.
 */
export interface Subject {
  id: string;
  name: string;
  code: string;
  description: string | null;
}

export interface SubjectFormPayload {
  name: string;
  code: string;
  description?: string | null;
}
