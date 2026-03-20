import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { curriculumService } from '@/services/curriculum.service';
import { extractMessage } from '@/lib/apiErrors';
import type { CurriculumFormPayload, DuplicateCurriculumPayload } from '@/types/curriculum.types';

const CURRICULUMS_KEY = ['curriculums'] as const;

export function useCurriculumList(page: number) {
  return useQuery({
    queryKey: [...CURRICULUMS_KEY, page],
    queryFn: () => curriculumService.list(page).then((res) => res.data),
  });
}

export function useCreateCurriculum() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: CurriculumFormPayload) => curriculumService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CURRICULUMS_KEY });
      toast.success('Programme pédagogique créé.');
    },
    // No onError toast: called through CurriculumFormSheet.
  });
}

export function useUpdateCurriculum(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<Omit<CurriculumFormPayload, 'level_id'>>) => curriculumService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CURRICULUMS_KEY });
      toast.success('Programme pédagogique mis à jour.');
    },
  });
}

export function useArchiveCurriculum() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => curriculumService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CURRICULUMS_KEY });
      toast.success('Programme pédagogique archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useDuplicateCurriculum() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: DuplicateCurriculumPayload) => curriculumService.duplicate(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CURRICULUMS_KEY });
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
