export type AttendanceStatus = 'PRESENT' | 'ABSENT' | 'LATE' | 'EXCUSED' | 'DISPENSED';
export type JustificationStatus = 'PENDING' | 'APPROVED' | 'REJECTED';

export const ATTENDANCE_STATUS_LABELS: Record<AttendanceStatus, string> = {
  PRESENT: 'Présent',
  ABSENT: 'Absent',
  LATE: 'Retard',
  EXCUSED: 'Excusé',
  DISPENSED: 'Dispensé',
};

export const JUSTIFICATION_STATUS_LABELS: Record<JustificationStatus, string> = {
  PENDING: 'En attente',
  APPROVED: 'Approuvée',
  REJECTED: 'Rejetée',
};

/** Mirrors AttendanceRecordResource. `enrollment` exposes student_id only — resolve the name via the enrollment list. */
export interface AttendanceRecord {
  id: string;
  lesson_session_id: string;
  enrollment_id: string;
  status: AttendanceStatus;
  actual_arrival_time: string | null;
  absence_reason: string | null;
  justification: string | null;
  justification_status: JustificationStatus | null;
  comment: string | null;
  enrollment?: { id: string; student_id: string };
}

/** PUT /attendance-records/{id}. actual_arrival_time only meaningful (and required server-side) when status is LATE. */
export interface UpdateAttendancePayload {
  status?: AttendanceStatus;
  actual_arrival_time?: string | null;
  absence_reason?: string | null;
  comment?: string | null;
}
