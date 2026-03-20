import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { schoolCalendarService } from '@/services/schoolCalendar.service';
import { extractMessage } from '@/lib/apiErrors';
import type { CreateSchoolCalendarPayload, HolidayEventFormPayload } from '@/types/schoolCalendar.types';

const CALENDARS_KEY = ['school-calendars'] as const;

export function useSchoolCalendarList() {
  return useQuery({
    queryKey: CALENDARS_KEY,
    queryFn: () => schoolCalendarService.list().then((res) => res.data),
  });
}

export function useSchoolCalendar(id: string | null) {
  return useQuery({
    queryKey: [...CALENDARS_KEY, 'detail', id],
    queryFn: () => schoolCalendarService.get(id as string).then((res) => res.data.data),
    enabled: id !== null,
  });
}

export function useCreateSchoolCalendar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: CreateSchoolCalendarPayload) => schoolCalendarService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: CALENDARS_KEY });
      toast.success('Calendrier scolaire créé.');
    },
    // No onError toast: called through CreateCalendarSheet (a FormSheet).
  });
}

export function useAddHoliday(calendarId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: HolidayEventFormPayload) => schoolCalendarService.addHoliday(calendarId, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [...CALENDARS_KEY, 'detail', calendarId] });
      toast.success('Événement de calendrier créé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useUpdateHoliday(calendarId: string, holidayId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: Partial<HolidayEventFormPayload>) => schoolCalendarService.updateHoliday(holidayId, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [...CALENDARS_KEY, 'detail', calendarId] });
      toast.success('Événement de calendrier mis à jour.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useRemoveHoliday(calendarId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (holidayId: string) => schoolCalendarService.removeHoliday(holidayId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [...CALENDARS_KEY, 'detail', calendarId] });
      toast.success('Événement de calendrier supprimé.');
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
