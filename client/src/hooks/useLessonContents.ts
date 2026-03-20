import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { lessonContentService } from '@/services/lessonContent.service';
import { extractMessage } from '@/lib/apiErrors';
import type { LessonContentFormPayload } from '@/types/lessonContent.types';

const LESSON_CONTENTS_KEY = ['lesson-contents'] as const;

export function useLessonContentList(page: number) {
  return useQuery({
    queryKey: [...LESSON_CONTENTS_KEY, page],
    queryFn: () => lessonContentService.list(page).then((res) => res.data),
  });
}

export function useCreateLessonContent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: LessonContentFormPayload) => lessonContentService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LESSON_CONTENTS_KEY });
      toast.success('Contenu enregistré.');
    },
    // No onError toast: called through LessonContentFormSheet.
  });
}

export function useUpdateLessonContent(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<Omit<LessonContentFormPayload, 'lesson_session_id'>>) =>
      lessonContentService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LESSON_CONTENTS_KEY });
      toast.success('Contenu mis à jour.');
    },
  });
}

export function useArchiveLessonContent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => lessonContentService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: LESSON_CONTENTS_KEY });
      toast.success('Contenu archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
