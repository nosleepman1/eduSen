import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type {
  ScheduleEntry,
  ScheduleEntryFormPayload,
  ScheduleEntryCreateResult,
  GenerateSessionsResult,
} from '@/types/scheduleEntry.types';

export const scheduleEntryService = {
  list(page = 1) {
    return api.get<ApiEnvelope<ScheduleEntry[]>>('/academic/schedule-entries', { params: { page } });
  },

  create(payload: ScheduleEntryFormPayload) {
    return api.post<ApiEnvelope<ScheduleEntryCreateResult>>('/academic/schedule-entries', payload);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/schedule-entries/${id}`);
  },

  generateSessions(id: string) {
    return api.post<ApiEnvelope<GenerateSessionsResult>>(`/academic/schedule-entries/${id}/generate-sessions`);
  },

  regenerate(id: string) {
    return api.post<ApiEnvelope<GenerateSessionsResult>>(`/academic/schedule-entries/${id}/regenerate`);
  },
};
