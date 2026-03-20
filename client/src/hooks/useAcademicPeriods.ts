import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { academicPeriodService } from '@/services/academicPeriod.service';
import { extractMessage } from '@/lib/apiErrors';
import type { AcademicPeriodFormPayload, PeriodGenerationMode } from '@/types/academicPeriod.types';

const PERIODS_KEY = ['academic-periods'] as const;

export function useAcademicPeriodList(page: number, academicYearId?: string) {
  return useQuery({
    queryKey: [...PERIODS_KEY, page, academicYearId],
    queryFn: () => academicPeriodService.list(page, academicYearId).then((res) => res.data),
  });
}

export function useCreateAcademicPeriod() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: AcademicPeriodFormPayload) => academicPeriodService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PERIODS_KEY });
      toast.success('Période académique créée.');
    },
    // No onError toast: called through AcademicPeriodFormSheet.
  });
}

export function useUpdateAcademicPeriod(id: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<Omit<AcademicPeriodFormPayload, 'academic_year_id'>>) =>
      academicPeriodService.update(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PERIODS_KEY });
      toast.success('Période académique mise à jour.');
    },
  });
}

function useTransition(action: (id: string) => Promise<unknown>, successMessage: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: action,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PERIODS_KEY });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useActivateAcademicPeriod() {
  return useTransition((id) => academicPeriodService.activate(id), 'Période académique activée.');
}

export function useCloseAcademicPeriod() {
  return useTransition((id) => academicPeriodService.close(id), 'Période académique clôturée.');
}

export function useArchiveAcademicPeriod() {
  return useTransition((id) => academicPeriodService.archive(id), 'Période académique archivée.');
}

export function useGeneratePeriods(academicYearId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (mode: PeriodGenerationMode) => academicPeriodService.generate(academicYearId, mode),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PERIODS_KEY });
      toast.success('Périodes académiques générées.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
