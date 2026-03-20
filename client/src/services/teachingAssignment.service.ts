import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type {
  TeachingAssignment,
  TeachingAssignmentFormPayload,
  ReassignPayload,
} from '@/types/teachingAssignment.types';

export const teachingAssignmentService = {
  list(page = 1) {
    return api.get<ApiEnvelope<TeachingAssignment[]>>('/academic/teaching-assignments', { params: { page } });
  },

  create(payload: TeachingAssignmentFormPayload) {
    return api.post<ApiEnvelope<TeachingAssignment>>('/academic/teaching-assignments', payload);
  },

  reassign(id: string, payload: ReassignPayload) {
    return api.patch<ApiEnvelope<TeachingAssignment>>(`/academic/teaching-assignments/${id}/reassign`, payload);
  },

  close(id: string) {
    return api.patch<ApiEnvelope<TeachingAssignment>>(`/academic/teaching-assignments/${id}/close`);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/teaching-assignments/${id}`);
  },
};
