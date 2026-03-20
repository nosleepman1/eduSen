import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { evaluationService } from '@/services/evaluation.service';
import { extractMessage } from '@/lib/apiErrors';
import type { EvaluationFormPayload } from '@/types/evaluation.types';

const EVALUATIONS_KEY = ['evaluations'] as const;

export function useEvaluationList(page: number) {
  return useQuery({
    queryKey: [...EVALUATIONS_KEY, page],
    queryFn: () => evaluationService.list(page).then((res) => res.data),
  });
}

export function useCreateEvaluation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: EvaluationFormPayload) => evaluationService.create(payload),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: EVALUATIONS_KEY });
      toast.success('Évaluation créée.');
      if (res.data.data.warning) {
        toast.warning(res.data.data.warning);
      }
    },
    // No onError toast for validation failures: called through EvaluationFormSheet.
  });
}

function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: EVALUATIONS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useOpenEvaluation() {
  return useTransition((id) => evaluationService.open(id), 'Saisie des notes ouverte.');
}

export function useCloseEvaluation() {
  return useTransition((id) => evaluationService.close(id), 'Évaluation clôturée.');
}

export function useLockEvaluation() {
  return useTransition((id) => evaluationService.lock(id), 'Évaluation verrouillée.');
}

export function useArchiveEvaluation() {
  return useTransition((id) => evaluationService.archive(id), 'Évaluation archivée.');
}
