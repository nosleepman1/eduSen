import { useMemo } from 'react';
import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTimetableList } from '@/hooks/useTimetables';
import { useTeachingAssignmentList } from '@/hooks/useTeachingAssignments';
import { useTeacherList } from '@/hooks/useTeachers';
import { useCurriculumList } from '@/hooks/useCurriculums';
import { useSubjectList } from '@/hooks/useSubjects';
import { useCreateScheduleEntry } from '@/hooks/useScheduleEntries';
import { DAY_LABELS } from '@/types/scheduleEntry.types';

const schema = z.object({
  timetable_id: z.string().uuid('Sélectionnez un emploi du temps.'),
  teaching_assignment_id: z.string().uuid('Sélectionnez une affectation.'),
  day_of_week: z.enum(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']),
  start_time: z.string().regex(/^\d{2}:\d{2}$/, 'Heure invalide.'),
  end_time: z.string().regex(/^\d{2}:\d{2}$/, 'Heure invalide.'),
  room: z.string().nullable().optional(),
});

const KNOWN_FIELDS = ['timetable_id', 'teaching_assignment_id', 'day_of_week', 'start_time', 'end_time', 'room'] as const;

interface ScheduleEntryFormSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * The server checks three conflicts at submit time (classroom, teacher,
 * room — RSE-004/005/006) and a non-blocking volume_warning against the
 * curriculum's weekly_hours; none of that is pre-validated client-side,
 * a 422 or a toast surfaces it after the fact.
 */
export function ScheduleEntryFormSheet({ open, onOpenChange }: ScheduleEntryFormSheetProps) {
  const { data: timetables } = useTimetableList(1);
  const { data: assignments } = useTeachingAssignmentList(1);
  const { data: teachers } = useTeacherList(1);
  const { data: curriculums } = useCurriculumList(1);
  const { data: subjects } = useSubjectList();
  const create = useCreateScheduleEntry();

  const teacherNames = useMemo(() => new Map((teachers?.data ?? []).map((t) => [t.id, t.full_name])), [teachers]);
  const subjectNames = useMemo(() => new Map((subjects?.data ?? []).map((s) => [s.id, s.name])), [subjects]);
  const curriculumSubject = useMemo(
    () => new Map((curriculums?.data ?? []).map((c) => [c.id, subjectNames.get(c.subject_id) ?? c.subject_id])),
    [curriculums, subjectNames]
  );

  return (
    <FormSheet
      open={open}
      onOpenChange={onOpenChange}
      title="Nouveau créneau"
      schema={schema}
      defaultValues={{
        timetable_id: '',
        teaching_assignment_id: '',
        day_of_week: 'MONDAY',
        start_time: '08:00',
        end_time: '10:00',
        room: '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => create.mutateAsync({ ...values, room: values.room || null }).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="timetable_id">Emploi du temps</Label>
            <select
              id="timetable_id"
              {...form.register('timetable_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(timetables?.data ?? []).map((t) => (
                <option key={t.id} value={t.id}>
                  {t.name} ({t.classroom?.name})
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.timetable_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="teaching_assignment_id">Affectation (matière / enseignant)</Label>
            <select
              id="teaching_assignment_id"
              {...form.register('teaching_assignment_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(assignments?.data ?? []).map((a) => (
                <option key={a.id} value={a.id}>
                  {curriculumSubject.get(a.curriculum_id) ?? a.curriculum_id} — {teacherNames.get(a.teacher_id) ?? a.teacher_id}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.teaching_assignment_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="day_of_week">Jour</Label>
            <select
              id="day_of_week"
              {...form.register('day_of_week')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              {Object.entries(DAY_LABELS).map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </div>
          <div className="flex gap-4">
            <div className="flex-1 space-y-2">
              <Label htmlFor="start_time">Début</Label>
              <Input id="start_time" type="time" {...form.register('start_time')} />
              <FieldError error={form.formState.errors.start_time} />
            </div>
            <div className="flex-1 space-y-2">
              <Label htmlFor="end_time">Fin</Label>
              <Input id="end_time" type="time" {...form.register('end_time')} />
              <FieldError error={form.formState.errors.end_time} />
            </div>
          </div>
          <div className="space-y-2">
            <Label htmlFor="room">Salle</Label>
            <Input id="room" {...form.register('room')} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
