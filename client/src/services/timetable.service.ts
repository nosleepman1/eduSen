import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Timetable, TimetableFormPayload } from '@/types/timetable.types';

export const timetableService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Timetable[]>>('/academic/timetables', { params: { page } });
  },

  create(payload: TimetableFormPayload) {
    return api.post<ApiEnvelope<Timetable>>('/academic/timetables', payload);
  },

  update(id: string, payload: Partial<Omit<TimetableFormPayload, 'classroom_id'>>) {
    return api.put<ApiEnvelope<Timetable>>(`/academic/timetables/${id}`, payload);
  },

  activate(id: string) {
    return api.patch<ApiEnvelope<Timetable>>(`/academic/timetables/${id}/activate`);
  },

  duplicate(id: string) {
    return api.post<ApiEnvelope<Timetable>>(`/academic/timetables/${id}/duplicate`);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/timetables/${id}`);
  },
};
