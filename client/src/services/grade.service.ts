import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Grade, UpdateGradePayload, AveragesResult } from '@/types/grade.types';

export const gradeService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Grade[]>>('/academic/grades', { params: { page } });
  },

  bulkCreate(evaluationId: string) {
    return api.post<ApiEnvelope<Grade[]>>(`/academic/evaluations/${evaluationId}/grades/bulk`);
  },

  update(id: string, payload: UpdateGradePayload) {
    return api.put<ApiEnvelope<Grade>>(`/academic/grades/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/grades/${id}`);
  },

  averages(enrollmentId: string, academicPeriodId?: string) {
    return api.get<ApiEnvelope<AveragesResult>>(`/academic/enrollments/${enrollmentId}/averages`, {
      params: academicPeriodId ? { academic_period_id: academicPeriodId } : undefined,
    });
  },
};
