import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { guardianService } from '@/services/guardian.service';
import { extractMessage } from '@/lib/apiErrors';
import type { GuardianFormPayload } from '@/types/guardian.types';

const GUARDIANS_KEY = ['guardians'] as const;

export function useGuardianList(page: number) {
  return useQuery({
    queryKey: [...GUARDIANS_KEY, page],
    queryFn: () => guardianService.list(page).then((res) => res.data),
  });
}

export function useCreateGuardian() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: GuardianFormPayload) => guardianService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: GUARDIANS_KEY });
      // The student screen's guardian picker reads the same list.
      queryClient.invalidateQueries({ queryKey: ['guardians', 'picker'] });
      toast.success('Parent créé.');
    },
    // No onError toast: only called through GuardianFormSheet, which already
    // renders field-level errors (e.g. the phone/email uniqueness 422) and
    // toasts whatever it can't attach to a field.
  });
}

export function useUpdateGuardian(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: GuardianFormPayload) => guardianService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: GUARDIANS_KEY });
      queryClient.invalidateQueries({ queryKey: ['guardians', 'picker'] });
      toast.success('Parent mis à jour.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useArchiveGuardian() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => guardianService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: GUARDIANS_KEY });
      queryClient.invalidateQueries({ queryKey: ['guardians', 'picker'] });
      toast.success('Parent archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
