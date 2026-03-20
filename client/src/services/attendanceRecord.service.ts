import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { AttendanceRecord, UpdateAttendancePayload } from '@/types/attendanceRecord.types';

export const attendanceRecordService = {
  list(page = 1) {
    return api.get<ApiEnvelope<AttendanceRecord[]>>('/academic/attendance-records', { params: { page } });
  },

  bulkCreate(lessonSessionId: string) {
    return api.post<ApiEnvelope<AttendanceRecord[]>>(`/academic/lesson-sessions/${lessonSessionId}/attendance/bulk`);
  },

  update(id: string, payload: UpdateAttendancePayload) {
    return api.put<ApiEnvelope<AttendanceRecord>>(`/academic/attendance-records/${id}`, payload);
  },

  justify(id: string, justification: string) {
    return api.patch<ApiEnvelope<AttendanceRecord>>(`/academic/attendance-records/${id}/justify`, { justification });
  },

  approveJustification(id: string) {
    return api.patch<ApiEnvelope<AttendanceRecord>>(`/academic/attendance-records/${id}/approve-justification`);
  },

  rejectJustification(id: string) {
    return api.patch<ApiEnvelope<AttendanceRecord>>(`/academic/attendance-records/${id}/reject-justification`);
  },

  archive(id: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/attendance-records/${id}`);
  },
};
