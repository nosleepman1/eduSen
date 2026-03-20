import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Evaluation, EvaluationFormPayload, EvaluationCreateResult } from '@/types/evaluation.types';

export const evaluationService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Evaluation[]>>('/academic/evaluations', { params: { page } });
  },

  create(payload: EvaluationFormPayload) {
    return api.post<ApiEnvelope<EvaluationCreateResult>>('/academic/evaluations', payload);
  },

  update(id: string, payload: Partial<Omit<EvaluationFormPayload, 'academic_period_id' | 'teaching_assignment_id'>>) {
    return api.put<ApiEnvelope<Evaluation>>(`/academic/evaluations/${id}`, payload);
  },

  open(id: string) {
    return api.patch<ApiEnvelope<Evaluation>>(`/academic/evaluations/${id}/open`);
  },

  close(id: string) {
    return api.patch<ApiEnvelope<Evaluation>>(`/academic/evaluations/${id}/close`);
  },

  lock(id: string) {
    return api.patch<ApiEnvelope<Evaluation>>(`/academic/evaluations/${id}/lock`);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/evaluations/${id}`);
  },
};
