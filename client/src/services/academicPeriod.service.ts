import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { AcademicPeriod, AcademicPeriodFormPayload, PeriodGenerationMode } from '@/types/academicPeriod.types';

export const academicPeriodService = {
  /** academic_year_id is the only filter AcademicPeriodController@index reads. */
  list(page = 1, academicYearId?: string) {
    return api.get<ApiEnvelope<AcademicPeriod[]>>('/academic/periods', {
      params: { page, academic_year_id: academicYearId || undefined },
    });
  },

  create(payload: AcademicPeriodFormPayload) {
    return api.post<ApiEnvelope<AcademicPeriod>>('/academic/periods', payload);
  },

  update(id: string, payload: Partial<Omit<AcademicPeriodFormPayload, 'academic_year_id'>>) {
    return api.put<ApiEnvelope<AcademicPeriod>>(`/academic/periods/${id}`, payload);
  },

  /** Also reactivates a CLOSED period — there is no separate reopen route for periods. */
  activate(id: string) {
    return api.patch<ApiEnvelope<AcademicPeriod>>(`/academic/periods/${id}/activate`);
  },

  close(id: string) {
    return api.patch<ApiEnvelope<AcademicPeriod>>(`/academic/periods/${id}/close`);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/periods/${id}`);
  },

  generate(academicYearId: string, mode: PeriodGenerationMode) {
    return api.post<ApiEnvelope<AcademicPeriod[]>>(`/academic/years/${academicYearId}/periods/generate`, { mode });
  },
};
