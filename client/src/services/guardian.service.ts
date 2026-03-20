import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Guardian, GuardianFormPayload } from '@/types/guardian.types';

/**
 * Same limitation as /personnel and /teachers: GuardianController@index takes
 * no Request at all, so there is no search or filter here either.
 */
export const guardianService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Guardian[]>>('/guardians', { params: { page } });
  },

  create(payload: GuardianFormPayload) {
    return api.post<ApiEnvelope<Guardian>>('/guardians', payload);
  },

  update(id: string, payload: GuardianFormPayload) {
    return api.put<ApiEnvelope<Guardian>>(`/guardians/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/guardians/${id}`);
  },
};
