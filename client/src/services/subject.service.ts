import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Subject, SubjectFormPayload } from '@/types/subject.types';

/** No pagination: SubjectController@index uses ->get(), not ->paginate(). */
export const subjectService = {
  list() {
    return api.get<ApiEnvelope<Subject[]>>('/academic/subjects');
  },

  create(payload: SubjectFormPayload) {
    return api.post<ApiEnvelope<Subject>>('/academic/subjects', payload);
  },

  update(id: string, payload: Partial<SubjectFormPayload>) {
    return api.put<ApiEnvelope<Subject>>(`/academic/subjects/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/subjects/${id}`);
  },
};
