import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type {
  Curriculum,
  CurriculumFormPayload,
  DuplicateCurriculumPayload,
  DuplicateCurriculumResult,
} from '@/types/curriculum.types';

export const curriculumService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Curriculum[]>>('/academic/curriculums', { params: { page } });
  },

  create(payload: CurriculumFormPayload) {
    return api.post<ApiEnvelope<Curriculum>>('/academic/curriculums', payload);
  },

  update(id: string, payload: Partial<Omit<CurriculumFormPayload, 'level_id'>>) {
    return api.put<ApiEnvelope<Curriculum>>(`/academic/curriculums/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/curriculums/${id}`);
  },

  duplicate(payload: DuplicateCurriculumPayload) {
    return api.post<ApiEnvelope<DuplicateCurriculumResult>>('/academic/curriculums/duplicate', payload);
  },
};
