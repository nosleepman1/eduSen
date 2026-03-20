import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { CreateTeacherPayload, Teacher, UpdateTeacherPayload } from '@/types/teacher.types';

/**
 * /teachers has no search/filter/sort query params, same as /personnel —
 * TeacherController@index takes no Request at all.
 */
export const teacherService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Teacher[]>>('/teachers', { params: { page } });
  },

  create(payload: CreateTeacherPayload) {
    return api.post<ApiEnvelope<Teacher>>('/teachers', payload);
  },

  update(id: string, payload: UpdateTeacherPayload) {
    return api.put<ApiEnvelope<Teacher>>(`/teachers/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/teachers/${id}`);
  },
};
