import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { academicYearService } from '@/services/academicYear.service';
import { extractMessage } from '@/lib/apiErrors';
import type { AcademicYearFormPayload } from '@/types/academicYear.types';

const YEARS_KEY = ['academic-years'] as const;

export function useAcademicYearList(page: number) {
  return useQuery({
    queryKey: [...YEARS_KEY, page],
    queryFn: () => academicYearService.list(page).then((res) => res.data),
  });
}

export function useCreateAcademicYear() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: AcademicYearFormPayload) => academicYearService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: YEARS_KEY });
      toast.success('Année scolaire créée.');
    },
    // No onError toast: called through AcademicYearFormSheet, which renders
    // field-level errors and toasts what it can't attach to a field.
  });
}

export function useUpdateAcademicYear(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<AcademicYearFormPayload>) => academicYearService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: YEARS_KEY });
      toast.success('Année scolaire mise à jour.');
    },
  });
}

/**
 * Shared by activate/close/reopen/archive: each is a state transition with no
 * field-level errors possible (the server rejects with a plain-message 422 —
 * overlap, already-closed, active-must-close-first — never a field name), so
 * a toast is always the right way to surface a rejection here.
 */
function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: YEARS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useActivateAcademicYear() {
  return useTransition((id) => academicYearService.activate(id), 'Année scolaire activée.');
}

export function useCloseAcademicYear() {
  return useTransition((id) => academicYearService.close(id), 'Année scolaire clôturée.');
}

export function useReopenAcademicYear() {
  return useTransition((id) => academicYearService.reopen(id), 'Année scolaire réouverte.');
}

export function useArchiveAcademicYear() {
  return useTransition((id) => academicYearService.archive(id), 'Année scolaire archivée.');
}
