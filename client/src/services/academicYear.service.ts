import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { AcademicYear, AcademicYearFormPayload } from '@/types/academicYear.types';

export const academicYearService = {
  list(page = 1) {
    return api.get<ApiEnvelope<AcademicYear[]>>('/academic/years', { params: { page } });
  },

  create(payload: AcademicYearFormPayload) {
    return api.post<ApiEnvelope<AcademicYear>>('/academic/years', payload);
  },

  update(id: string, payload: Partial<AcademicYearFormPayload>) {
    return api.put<ApiEnvelope<AcademicYear>>(`/academic/years/${id}`, payload);
  },

  activate(id: string) {
    return api.patch<ApiEnvelope<AcademicYear>>(`/academic/years/${id}/activate`);
  },

  close(id: string) {
    return api.patch<ApiEnvelope<AcademicYear>>(`/academic/years/${id}/close`);
  },

  reopen(id: string) {
    return api.patch<ApiEnvelope<AcademicYear>>(`/academic/years/${id}/reopen`);
  },

  /** Only DRAFT/CLOSED can be archived — AcademicYearService::archive rejects an ACTIVE year. */
  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/years/${id}`);
  },
};
