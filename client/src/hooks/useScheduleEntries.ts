import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { scheduleEntryService } from '@/services/scheduleEntry.service';
import { extractMessage } from '@/lib/apiErrors';
import type { ScheduleEntryFormPayload } from '@/types/scheduleEntry.types';

const SCHEDULE_ENTRIES_KEY = ['schedule-entries'] as const;

export function useScheduleEntryList(page: number) {
  return useQuery({
    queryKey: [...SCHEDULE_ENTRIES_KEY, page],
    queryFn: () => scheduleEntryService.list(page).then((res) => res.data),
  });
}

export function useCreateScheduleEntry() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: ScheduleEntryFormPayload) => scheduleEntryService.create(payload),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: SCHEDULE_ENTRIES_KEY });
      toast.success('Créneau créé.');
      if (res.data.data.volume_warning) {
        toast.warning(res.data.data.volume_warning);
      }
    },
    // No onError toast for validation failures: called through ScheduleEntryFormSheet.
  });
}

export function useArchiveScheduleEntry() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => scheduleEntryService.archive(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: SCHEDULE_ENTRIES_KEY });
      toast.success('Créneau archivé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

function useGeneration(action: (id: string) => ReturnType<typeof scheduleEntryService.generateSessions>) {
  return useMutation({
    mutationFn: action,
    onSuccess: (res) => {
      const { created, skipped_holiday, skipped_existing } = res.data.data;
      toast.success(
        `${created.length} séance(s) générée(s)` +
          (skipped_holiday ? `, ${skipped_holiday} ignorée(s) (jour férié)` : '') +
          (skipped_existing ? `, ${skipped_existing} déjà existante(s)` : '')
      );
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useGenerateSessions() {
  return useGeneration((id) => scheduleEntryService.generateSessions(id));
}

export function useRegenerateSessions() {
  return useGeneration((id) => scheduleEntryService.regenerate(id));
}
