import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type {
  CreateSchoolCalendarPayload,
  HolidayEventFormPayload,
  HolidayEvent,
  SchoolCalendar,
} from '@/types/schoolCalendar.types';

/** No pagination: SchoolCalendarController@index uses ->get(), not ->paginate(). */
export const schoolCalendarService = {
  list() {
    return api.get<ApiEnvelope<SchoolCalendar[]>>('/academic/school-calendars');
  },

  /** Only this call eager-loads holiday_events. */
  get(id: string) {
    return api.get<ApiEnvelope<SchoolCalendar>>(`/academic/school-calendars/${id}`);
  },

  create(payload: CreateSchoolCalendarPayload) {
    return api.post<ApiEnvelope<SchoolCalendar>>('/academic/school-calendars', payload);
  },

  addHoliday(calendarId: string, payload: HolidayEventFormPayload) {
    return api.post<ApiEnvelope<HolidayEvent>>(`/academic/school-calendars/${calendarId}/holidays`, payload);
  },

  updateHoliday(holidayId: string, payload: Partial<HolidayEventFormPayload>) {
    return api.put<ApiEnvelope<HolidayEvent>>(`/academic/school-calendars/holidays/${holidayId}`, payload);
  },

  /** Soft-deleted server-side, but no restore route is exposed for holidays. */
  removeHoliday(holidayId: string) {
    return api.delete<ApiEnvelope<null>>(`/academic/school-calendars/holidays/${holidayId}`);
  },
};
