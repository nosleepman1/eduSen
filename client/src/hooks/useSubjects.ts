import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { subjectService } from '@/services/subject.service';
import { extractMessage } from '@/lib/apiErrors';
import type { SubjectFormPayload } from '@/types/subject.types';

const SUBJECTS_KEY = ['subjects'] as const;

export function useSubjectList() {
  return useQuery({
    queryKey: SUBJECTS_KEY,
    queryFn: () => subjectService.list().then((res) => res.data),
  });
}

export function useCreateSubject() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: SubjectFormPayload) => subjectService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: SUBJECTS_KEY });
      toast.success('Matière créée.');
    },
    // No onError toast: called through SubjectFormSheet.
  });
}

export function useUpdateSubject(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<SubjectFormPayload>) => subjectService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: SUBJECTS_KEY });
      toast.success('Matière mise à jour.');
    },
  });
}

export function useArchiveSubject() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => subjectService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: SUBJECTS_KEY });
      toast.success('Matière archivée.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
