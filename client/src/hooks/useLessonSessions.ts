import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { lessonSessionService } from '@/services/lessonSession.service';
import { extractMessage } from '@/lib/apiErrors';
import type { CancelPayload, PostponePayload, ValidatePayload } from '@/types/lessonSession.types';

const LESSON_SESSIONS_KEY = ['lesson-sessions'] as const;

export function useLessonSessionList(page: number) {
  return useQuery({
    queryKey: [...LESSON_SESSIONS_KEY, page],
    queryFn: () => lessonSessionService.list(page).then((res) => res.data),
  });
}

function useTransition<TPayload = void>(
  action: (id: string, payload: TPayload) => Promise<unknown>,
  successMessage: string
) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: TPayload }) => action(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LESSON_SESSIONS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useStartSession() {
  return useTransition<void>((id) => lessonSessionService.start(id), 'Séance démarrée.');
}

export function useFinishSession() {
  return useTransition<void>((id) => lessonSessionService.finish(id), 'Séance terminée.');
}

export function useCancelSession() {
  return useTransition<CancelPayload>((id, payload) => lessonSessionService.cancel(id, payload), 'Séance annulée.');
}

export function usePostponeSession() {
  return useTransition<PostponePayload>((id, payload) => lessonSessionService.postpone(id, payload), 'Séance reportée.');
}

export function useValidateSession() {
  return useTransition<ValidatePayload>(
    (id, payload) => lessonSessionService.validate(id, payload),
    'Séance validée administrativement.'
  );
}
