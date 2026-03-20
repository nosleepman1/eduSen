import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { LinkGuardianPayload, Student, StudentFormPayload } from '@/types/student.types';

export const studentService = {
  /** The only IAM list endpoint with real search support (firstname/lastname only). */
  list(page = 1, search = '') {
    return api.get<ApiEnvelope<Student[]>>('/students', { params: { page, search: search || undefined } });
  },

  get(id: string) {
    return api.get<ApiEnvelope<Student>>(`/students/${id}`);
  },

  create(payload: StudentFormPayload) {
    return api.post<ApiEnvelope<Student>>('/students', payload);
  },

  update(id: string, payload: StudentFormPayload) {
    return api.put<ApiEnvelope<Student>>(`/students/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/students/${id}`);
  },

  /** Upsert, not append: linking an already-linked guardian_id updates their pivot. */
  linkGuardian(id: string, payload: LinkGuardianPayload) {
    return api.post<ApiEnvelope<Student>>(`/students/${id}/guardians`, payload);
  },
};
