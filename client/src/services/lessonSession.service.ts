import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { LessonSession, CancelPayload, PostponePayload, ValidatePayload } from '@/types/lessonSession.types';

export const lessonSessionService = {
  list(page = 1) {
    return api.get<ApiEnvelope<LessonSession[]>>('/academic/lesson-sessions', { params: { page } });
  },

  today() {
    return api.get<ApiEnvelope<LessonSession[]>>('/academic/lesson-sessions/today');
  },

  start(id: string) {
    return api.post<ApiEnvelope<LessonSession>>(`/academic/lesson-sessions/${id}/start`);
  },

  finish(id: string) {
    return api.post<ApiEnvelope<LessonSession>>(`/academic/lesson-sessions/${id}/finish`);
  },

  cancel(id: string, payload: CancelPayload) {
    return api.post<ApiEnvelope<LessonSession>>(`/academic/lesson-sessions/${id}/cancel`, payload);
  },

  postpone(id: string, payload: PostponePayload) {
    return api.post<ApiEnvelope<LessonSession>>(`/academic/lesson-sessions/${id}/postpone`, payload);
  },

  validate(id: string, payload: ValidatePayload) {
    return api.post<ApiEnvelope<LessonSession>>(`/academic/lesson-sessions/${id}/validate`, payload);
  },

  restore(id: string) {
    return api.patch<ApiEnvelope<LessonSession>>(`/academic/lesson-sessions/${id}/restore`);
  },
};
