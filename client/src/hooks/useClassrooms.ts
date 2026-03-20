import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { classroomService } from '@/services/classroom.service';
import { extractMessage } from '@/lib/apiErrors';
import type { ClassroomFormPayload } from '@/types/classroom.types';

const CLASSROOMS_KEY = ['classrooms'] as const;

export function useClassroomList(page: number) {
  return useQuery({
    queryKey: [...CLASSROOMS_KEY, page],
    queryFn: () => classroomService.list(page).then((res) => res.data),
  });
}

export function useCreateClassroom() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: ClassroomFormPayload) => classroomService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CLASSROOMS_KEY });
      toast.success('Classe créée.');
    },
    // No onError toast: called through ClassroomFormSheet.
  });
}

export function useUpdateClassroom(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<Omit<ClassroomFormPayload, 'level_id'>>) => classroomService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CLASSROOMS_KEY });
      toast.success('Classe mise à jour.');
    },
  });
}

function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CLASSROOMS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useOpenClassroom() {
  return useTransition((id) => classroomService.open(id), 'Classe ouverte.');
}

export function useCloseClassroom() {
  return useTransition((id) => classroomService.close(id), 'Classe fermée.');
}

export function useArchiveClassroom() {
  return useTransition((id) => classroomService.archive(id), 'Classe archivée.');
}

export function useAssignPrincipalTeacher(classroomId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (mainTeacherId: string | null) => classroomService.assignPrincipalTeacher(classroomId, mainTeacherId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CLASSROOMS_KEY });
      toast.success('Professeur principal assigné.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
