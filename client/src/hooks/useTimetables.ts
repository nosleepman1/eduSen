import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { timetableService } from '@/services/timetable.service';
import { extractMessage } from '@/lib/apiErrors';
import type { TimetableFormPayload } from '@/types/timetable.types';

const TIMETABLES_KEY = ['timetables'] as const;

export function useTimetableList(page: number) {
  return useQuery({
    queryKey: [...TIMETABLES_KEY, page],
    queryFn: () => timetableService.list(page).then((res) => res.data),
  });
}

export function useCreateTimetable() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: TimetableFormPayload) => timetableService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TIMETABLES_KEY });
      toast.success('Emploi du temps créé.');
    },
    // No onError toast: called through TimetableFormSheet.
  });
}

export function useUpdateTimetable(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<Omit<TimetableFormPayload, 'classroom_id'>>) => timetableService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TIMETABLES_KEY });
      toast.success('Emploi du temps mis à jour.');
    },
  });
}

function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TIMETABLES_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useActivateTimetable() {
  return useTransition((id) => timetableService.activate(id), 'Emploi du temps activé.');
}

export function useDuplicateTimetable() {
  return useTransition((id) => timetableService.duplicate(id), 'Emploi du temps dupliqué.');
}

export function useArchiveTimetable() {
  return useTransition((id) => timetableService.archive(id), 'Emploi du temps archivé.');
}
