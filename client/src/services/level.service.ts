import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Level, LevelFormPayload, ReorderLevelsPayload } from '@/types/level.types';

export const levelService = {
  /** academic_year_id is not a supported filter on LevelController@index — verified: no Request read there either. */
  list(page = 1) {
    return api.get<ApiEnvelope<Level[]>>('/academic/levels', { params: { page } });
  },

  create(payload: LevelFormPayload) {
    return api.post<ApiEnvelope<Level>>('/academic/levels', payload);
  },

  update(id: string, payload: Partial<Omit<LevelFormPayload, 'academic_year_id'>>) {
    return api.put<ApiEnvelope<Level>>(`/academic/levels/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/levels/${id}`);
  },

  reorder(payload: ReorderLevelsPayload) {
    return api.patch<ApiEnvelope<Level[]>>('/academic/levels/reorder', payload);
  },
};
