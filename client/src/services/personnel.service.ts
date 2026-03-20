import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { Personnel, UpdatePersonnelPayload, UpdatePersonnelRolesPayload } from '@/types/personnel.types';

/**
 * /personnel has no search/filter/sort query params — verified against
 * PersonnelController@index, which takes no Request at all. `page` is the
 * only parameter the server actually reads.
 */
export const personnelService = {
  list(page = 1) {
    return api.get<ApiEnvelope<Personnel[]>>('/personnel', { params: { page } });
  },

  update(id: string, payload: UpdatePersonnelPayload) {
    return api.put<ApiEnvelope<Personnel>>(`/personnel/${id}`, payload);
  },

  updateRoles(id: string, payload: UpdatePersonnelRolesPayload) {
    return api.patch<ApiEnvelope<null>>(`/personnel/${id}/roles`, payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/personnel/${id}`);
  },

  restore(id: string) {
    return api.patch<ApiEnvelope<Personnel>>(`/personnel/${id}/restore`);
  },
};
