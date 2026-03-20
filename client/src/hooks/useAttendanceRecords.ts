import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { attendanceRecordService } from '@/services/attendanceRecord.service';
import { extractMessage } from '@/lib/apiErrors';
import type { UpdateAttendancePayload } from '@/types/attendanceRecord.types';

const ATTENDANCE_RECORDS_KEY = ['attendance-records'] as const;

export function useAttendanceRecordList(page: number) {
  return useQuery({
    queryKey: [...ATTENDANCE_RECORDS_KEY, page],
    queryFn: () => attendanceRecordService.list(page).then((res) => res.data),
  });
}

export function useBulkCreateAttendance() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (lessonSessionId: string) => attendanceRecordService.bulkCreate(lessonSessionId),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ATTENDANCE_RECORDS_KEY });
      toast.success(`${res.data.data.length} présence(s) générée(s).`);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useUpdateAttendance() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: UpdateAttendancePayload }) =>
      attendanceRecordService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ATTENDANCE_RECORDS_KEY });
      toast.success('Présence mise à jour.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useJustifyAttendance() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, justification }: { id: string; justification: string }) =>
      attendanceRecordService.justify(id, justification),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ATTENDANCE_RECORDS_KEY });
      toast.success('Justification soumise.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ATTENDANCE_RECORDS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useApproveJustification() {
  return useTransition((id) => attendanceRecordService.approveJustification(id), 'Justification approuvée.');
}

export function useRejectJustification() {
  return useTransition((id) => attendanceRecordService.rejectJustification(id), 'Justification rejetée.');
}

export function useArchiveAttendance() {
  return useTransition((id) => attendanceRecordService.archive(id), 'Présence archivée.');
}
