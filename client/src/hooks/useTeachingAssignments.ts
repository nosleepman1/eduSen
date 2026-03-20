import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { teachingAssignmentService } from '@/services/teachingAssignment.service';
import { extractMessage } from '@/lib/apiErrors';
import type { TeachingAssignmentFormPayload, ReassignPayload } from '@/types/teachingAssignment.types';

const TEACHING_ASSIGNMENTS_KEY = ['teaching-assignments'] as const;

export function useTeachingAssignmentList(page: number) {
  return useQuery({
    queryKey: [...TEACHING_ASSIGNMENTS_KEY, page],
    queryFn: () => teachingAssignmentService.list(page).then((res) => res.data),
  });
}

export function useCreateTeachingAssignment() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: TeachingAssignmentFormPayload) => teachingAssignmentService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TEACHING_ASSIGNMENTS_KEY });
      toast.success('Affectation créée.');
    },
    // No onError toast: called through TeachingAssignmentFormSheet.
  });
}

export function useReassignTeachingAssignment(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: ReassignPayload) => teachingAssignmentService.reassign(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TEACHING_ASSIGNMENTS_KEY });
      toast.success('Enseignant réaffecté.');
    },
    // No onError toast: called through ReassignDialog.
  });
}

function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TEACHING_ASSIGNMENTS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useCloseTeachingAssignment() {
  return useTransition((id) => teachingAssignmentService.close(id), 'Affectation clôturée.');
}

export function useArchiveTeachingAssignment() {
  return useTransition((id) => teachingAssignmentService.archive(id), 'Affectation archivée.');
}
