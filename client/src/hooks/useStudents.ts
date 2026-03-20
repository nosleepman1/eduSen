import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { studentService } from '@/services/student.service';
import { guardianService } from '@/services/guardian.service';
import { extractMessage } from '@/lib/apiErrors';
import type { LinkGuardianPayload, StudentFormPayload } from '@/types/student.types';

const STUDENTS_KEY = ['students'] as const;

export function useStudentList(page: number, search: string) {
  return useQuery({
    queryKey: [...STUDENTS_KEY, page, search],
    queryFn: () => studentService.list(page, search).then((res) => res.data),
  });
}

export function useStudent(id: string | null) {
  return useQuery({
    queryKey: [...STUDENTS_KEY, 'detail', id],
    // Unwrapped to the record itself, unlike the list hooks: a single-item
    // fetch has no pagination meta worth exposing alongside it.
    queryFn: () => studentService.get(id as string).then((res) => res.data.data),
    enabled: id !== null,
  });
}

export function useGuardianPicker(page = 1) {
  return useQuery({
    queryKey: ['guardians', 'picker', page],
    queryFn: () => guardianService.list(page).then((res) => res.data),
  });
}

export function useCreateStudent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: StudentFormPayload) => studentService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: STUDENTS_KEY });
      toast.success('Élève créé.');
    },
    // No onError toast: only ever called through StudentFormSheet, which
    // already renders field-level errors and toasts whatever it can't attach
    // to a field. A toast here would duplicate that.
  });
}

export function useUpdateStudent(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: StudentFormPayload) => studentService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: STUDENTS_KEY });
      toast.success('Élève mis à jour.');
    },
  });
}

export function useArchiveStudent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => studentService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: STUDENTS_KEY });
      toast.success('Élève archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useLinkGuardian(studentId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: LinkGuardianPayload) => studentService.linkGuardian(studentId, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [...STUDENTS_KEY, 'detail', studentId] });
      toast.success('Tuteur associé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
