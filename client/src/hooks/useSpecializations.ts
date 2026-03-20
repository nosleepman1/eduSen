import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { specializationService } from '@/services/specialization.service';
import { extractMessage } from '@/lib/apiErrors';
import type { SpecializationFormPayload } from '@/types/specialization.types';

const SPECIALIZATIONS_KEY = ['specializations'] as const;

export function useSpecializationList() {
  return useQuery({
    queryKey: SPECIALIZATIONS_KEY,
    queryFn: () => specializationService.list().then((res) => res.data),
  });
}

export function useCreateSpecialization() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: SpecializationFormPayload) => specializationService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: SPECIALIZATIONS_KEY });
      toast.success('Spécialisation créée.');
    },
    // No onError toast: called through SpecializationFormSheet.
  });
}

export function useUpdateSpecialization(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<SpecializationFormPayload>) => specializationService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: SPECIALIZATIONS_KEY });
      toast.success('Spécialisation mise à jour.');
    },
  });
}

export function useArchiveSpecialization() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => specializationService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: SPECIALIZATIONS_KEY });
      toast.success('Spécialisation archivée.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
