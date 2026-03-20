import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { bulletinService } from '@/services/bulletin.service';
import { extractMessage } from '@/lib/apiErrors';
import type { GenerateBulletinPayload, GenerateClassBulletinsPayload } from '@/types/bulletin.types';

const BULLETINS_KEY = ['bulletins'] as const;

export function useBulletinList(page: number) {
  return useQuery({
    queryKey: [...BULLETINS_KEY, page],
    queryFn: () => bulletinService.list(page).then((res) => res.data),
  });
}

export function useGenerateBulletin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: GenerateBulletinPayload) => bulletinService.generate(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: BULLETINS_KEY });
      toast.success('Bulletin généré.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useGenerateClassBulletins() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: GenerateClassBulletinsPayload) => bulletinService.generateClass(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: BULLETINS_KEY });
      toast.success('Génération des bulletins de la classe mise en file d\'attente.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useValidateBulletin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, comment }: { id: string; comment?: string | null }) => bulletinService.validate(id, comment),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: BULLETINS_KEY });
      toast.success('Bulletin validé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function usePublishBulletin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => bulletinService.publish(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: BULLETINS_KEY });
      toast.success('Bulletin publié.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useLockBulletin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => bulletinService.lock(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: BULLETINS_KEY });
      toast.success('Bulletin verrouillé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useArchiveBulletin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => bulletinService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: BULLETINS_KEY });
      toast.success('Bulletin archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
