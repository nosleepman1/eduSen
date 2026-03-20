import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { personnelService } from '@/services/personnel.service';
import { extractMessage } from '@/lib/apiErrors';
import type { UpdatePersonnelPayload, UpdatePersonnelRolesPayload } from '@/types/personnel.types';

const PERSONNEL_KEY = ['personnel'] as const;

export function usePersonnelList(page: number) {
  return useQuery({
    queryKey: [...PERSONNEL_KEY, page],
    queryFn: () => personnelService.list(page).then((res) => res.data),
  });
}

export function useUpdatePersonnel(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: UpdatePersonnelPayload) => personnelService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PERSONNEL_KEY });
      toast.success('Membre du personnel mis à jour.');
    },
  });
}

export function useUpdatePersonnelRoles(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: UpdatePersonnelRolesPayload) => personnelService.updateRoles(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PERSONNEL_KEY });
      toast.success('Rôles mis à jour.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useArchivePersonnel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => personnelService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PERSONNEL_KEY });
      toast.success('Membre du personnel archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
