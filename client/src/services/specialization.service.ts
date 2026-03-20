import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Specialization, SpecializationFormPayload } from '@/types/specialization.types';

/** No pagination: SpecializationController@index uses ->get(), not ->paginate(). */
export const specializationService = {
  list() {
    return api.get<ApiEnvelope<Specialization[]>>('/academic/specializations');
  },

  create(payload: SpecializationFormPayload) {
    return api.post<ApiEnvelope<Specialization>>('/academic/specializations', payload);
  },

  update(id: string, payload: Partial<SpecializationFormPayload>) {
    return api.put<ApiEnvelope<Specialization>>(`/academic/specializations/${id}`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/specializations/${id}`);
  },
};
