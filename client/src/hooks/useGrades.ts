import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { gradeService } from '@/services/grade.service';
import { extractMessage } from '@/lib/apiErrors';
import type { UpdateGradePayload } from '@/types/grade.types';

const GRADES_KEY = ['grades'] as const;

export function useGradeList(page: number) {
  return useQuery({
    queryKey: [...GRADES_KEY, page],
    queryFn: () => gradeService.list(page).then((res) => res.data),
  });
}

export function useBulkCreateGrades() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (evaluationId: string) => gradeService.bulkCreate(evaluationId),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: GRADES_KEY });
      toast.success(`${res.data.data.length} note(s) générée(s).`);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useUpdateGrade() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: UpdateGradePayload }) => gradeService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: GRADES_KEY });
      toast.success('Note mise à jour.');
    },
    // No onError toast: called through GradeValueDialog (RG-004 range errors shown as field errors).
  });
}

export function useArchiveGrade() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => gradeService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: GRADES_KEY });
      toast.success('Note archivée.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useEnrollmentAverages(enrollmentId: string | null) {
  return useQuery({
    queryKey: ['enrollment-averages', enrollmentId],
    queryFn: () => gradeService.averages(enrollmentId!).then((res) => res.data),
    enabled: enrollmentId !== null,
  });
}
