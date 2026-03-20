import api from '../api/axios';
import type { PaginatedSchoolRequests } from '../types/school.types';

export const SchoolsService = {
  async getSchoolRequests(page = 1, per_page = 15): Promise<PaginatedSchoolRequests> {
    const response = await api.get<{ data: PaginatedSchoolRequests }>('/api/v1/platform/registration-requests', {
      params: { page, per_page }
    });
    // Assuming backend returns ApiResponse::success('...', $service->pendingRequests())
    // The actual data is usually inside response.data.data
    return response.data.data;
  },

  async acceptSchoolRequest(id: string): Promise<void> {
    await api.patch(`/api/v1/platform/registration-requests/${id}/approve`);
  },

  async rejectSchoolRequest(id: string, reason: string): Promise<void> {
    await api.patch(`/api/v1/platform/registration-requests/${id}/reject`, {
      rejection_reason: reason,
    });
  },
};
