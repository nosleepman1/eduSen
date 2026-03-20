import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { levelService } from '@/services/level.service';
import { extractMessage } from '@/lib/apiErrors';
import type { LevelFormPayload, ReorderLevelsPayload } from '@/types/level.types';

const LEVELS_KEY = ['levels'] as const;

/**
 * LevelController@index has no filter and no year grouping — all of a
 * school's levels across every year come back in one paginated list, sorted
 * by display_order (which is scoped per year, so this list is not sorted
 * meaningfully across years). The page filters by year client-side after
 * fetching, on the already-loaded page only — not a server capability.
 */
export function useLevelList(page: number) {
  return useQuery({
    queryKey: [...LEVELS_KEY, page],
    queryFn: () => levelService.list(page).then((res) => res.data),
  });
}

export function useCreateLevel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: LevelFormPayload) => levelService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LEVELS_KEY });
      toast.success('Niveau créé.');
    },
    // No onError toast: called through LevelFormSheet.
  });
}

export function useUpdateLevel(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<Omit<LevelFormPayload, 'academic_year_id'>>) => levelService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LEVELS_KEY });
      toast.success('Niveau mis à jour.');
    },
  });
}

export function useArchiveLevel() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => levelService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LEVELS_KEY });
      toast.success('Niveau archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useReorderLevels() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: ReorderLevelsPayload) => levelService.reorder(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LEVELS_KEY });
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
