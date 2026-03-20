import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { enrollmentService } from '@/services/enrollment.service';
import { extractMessage } from '@/lib/apiErrors';
import type { EnrollmentFormPayload, TransferPayload, WithdrawPayload } from '@/types/enrollment.types';

const ENROLLMENTS_KEY = ['enrollments'] as const;

export function useEnrollmentList(page: number) {
  return useQuery({
    queryKey: [...ENROLLMENTS_KEY, page],
    queryFn: () => enrollmentService.list(page).then((res) => res.data),
  });
}

export function useCreateEnrollment() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: EnrollmentFormPayload) => enrollmentService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ENROLLMENTS_KEY });
      toast.success('Élève inscrit.');
    },
    // No onError toast: called through EnrollmentFormSheet.
  });
}

export function useTransferEnrollment(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: TransferPayload) => enrollmentService.transfer(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ENROLLMENTS_KEY });
      toast.success('Élève transféré.');
    },
    // No onError toast: called through TransferEnrollmentDialog.
  });
}

function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ENROLLMENTS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useWithdrawEnrollment() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: WithdrawPayload }) => enrollmentService.withdraw(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ENROLLMENTS_KEY });
      toast.success('Inscription clôturée.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useArchiveEnrollment() {
  return useTransition((id) => enrollmentService.archive(id), 'Inscription archivée.');
}

export function useRestoreEnrollment() {
  return useTransition((id) => enrollmentService.restore(id), 'Inscription restaurée.');
}
