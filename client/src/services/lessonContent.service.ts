import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { LessonContent, LessonContentFormPayload } from '@/types/lessonContent.types';

export const lessonContentService = {
  list(page = 1) {
    return api.get<ApiEnvelope<LessonContent[]>>('/academic/lesson-contents', { params: { page } });
  },

  create(payload: LessonContentFormPayload) {
    return api.post<ApiEnvelope<LessonContent>>('/academic/lesson-contents', payload);
  },

  update(id: string, payload: Partial<Omit<LessonContentFormPayload, 'lesson_session_id'>>) {
    return api.put<ApiEnvelope<LessonContent>>(`/academic/lesson-contents/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/lesson-contents/${id}`);
  },
};
