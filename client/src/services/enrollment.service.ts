import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Enrollment, EnrollmentFormPayload, TransferPayload, WithdrawPayload } from '@/types/enrollment.types';

export const enrollmentService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Enrollment[]>>('/academic/enrollments', { params: { page } });
  },

  create(payload: EnrollmentFormPayload) {
    return api.post<ApiEnvelope<Enrollment>>('/academic/enrollments', payload);
  },

  transfer(id: string, payload: TransferPayload) {
    return api.patch<ApiEnvelope<Enrollment>>(`/academic/enrollments/${id}/transfer`, payload);
  },

  withdraw(id: string, payload: WithdrawPayload) {
    return api.patch<ApiEnvelope<Enrollment>>(`/academic/enrollments/${id}/withdraw`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/enrollments/${id}`);
  },

  restore(id: string) {
    return api.patch<ApiEnvelope<Enrollment>>(`/academic/enrollments/${id}/restore`);
  },
};
