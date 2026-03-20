/** Mirrors LevelResource. */
export interface Level {
  id: string;
  academic_year_id: string;
  specialization_id: string | null;
  name: string;
  code: string;
  display_order: number;
  description: string | null;
}

/**
 * display_order is optional on create: LevelService::create() auto-assigns
 * max(display_order)+1 within the year when omitted, so the form never sends
 * it — the create screen has no ordering field.
 */
export interface LevelFormPayload {
  academic_year_id: string;
  specialization_id?: string | null;
  name: string;
  code: string;
  description?: string | null;
}

/** PATCH /academic/levels/reorder. Reassigns display_order 1..n for the given order. */
export interface ReorderLevelsPayload {
  academic_year_id: string;
  ordered_ids: string[];
}
