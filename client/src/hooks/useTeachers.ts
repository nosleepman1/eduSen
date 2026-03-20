import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { teacherService } from '@/services/teacher.service';
import { extractMessage } from '@/lib/apiErrors';
import type { CreateTeacherPayload, UpdateTeacherPayload } from '@/types/teacher.types';

const TEACHERS_KEY = ['teachers'] as const;

export function useTeacherList(page: number) {
  return useQuery({
    queryKey: [...TEACHERS_KEY, page],
    queryFn: () => teacherService.list(page).then((res) => res.data),
  });
}

export function useCreateTeacher() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: CreateTeacherPayload) => teacherService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TEACHERS_KEY });
      // Promoting a personnel to teacher doesn't change their `roles` list,
      // but the personnel screen's implicit "already a teacher" filter needs
      // the fresh teacher list — invalidate it too rather than let it go stale.
      queryClient.invalidateQueries({ queryKey: ['personnel'] });
      toast.success('Profil enseignant créé.');
    },
  });
}

export function useUpdateTeacher(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: UpdateTeacherPayload) => teacherService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TEACHERS_KEY });
      toast.success('Enseignant mis à jour.');
    },
  });
}

export function useArchiveTeacher() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => teacherService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TEACHERS_KEY });
      queryClient.invalidateQueries({ queryKey: ['personnel'] });
      toast.success('Enseignant archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
