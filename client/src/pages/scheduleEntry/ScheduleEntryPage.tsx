import { useMemo, useState } from 'react';
import { RiAddLine, RiPlayCircleLine, RiRefreshLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useTimetableList } from '@/hooks/useTimetables';
import { useTeacherList } from '@/hooks/useTeachers';
import { useCurriculumList } from '@/hooks/useCurriculums';
import { useSubjectList } from '@/hooks/useSubjects';
import {
  useScheduleEntryList,
  useArchiveScheduleEntry,
  useGenerateSessions,
  useRegenerateSessions,
} from '@/hooks/useScheduleEntries';
import { ScheduleEntryFormSheet } from './ScheduleEntryFormSheet';
import { DAY_LABELS, SCHEDULE_ENTRY_STATUS_LABELS, type ScheduleEntry, type ScheduleEntryStatus } from '@/types/scheduleEntry.types';

const STATUS_TONE: Record<ScheduleEntryStatus, Tone> = {
  ACTIVE: 'success',
  ARCHIVED: 'danger',
};

export default function ScheduleEntryPage() {
  const [page, setPage] = useState(1);
  const [creating, setCreating] = useState(false);
  const [archiving, setArchiving] = useState<ScheduleEntry | null>(null);

  const { data, isLoading } = useScheduleEntryList(page);
  const { data: timetables } = useTimetableList(1);
  const { data: teachers } = useTeacherList(1);
  const { data: curriculums } = useCurriculumList(1);
  const { data: subjects } = useSubjectList();
  const archive = useArchiveScheduleEntry();
  const generateSessions = useGenerateSessions();
  const regenerate = useRegenerateSessions();

  const timetableNames = useMemo(() => new Map((timetables?.data ?? []).map((t) => [t.id, t.name])), [timetables]);
  const teacherNames = useMemo(() => new Map((teachers?.data ?? []).map((t) => [t.id, t.full_name])), [teachers]);
  const subjectNames = useMemo(() => new Map((subjects?.data ?? []).map((s) => [s.id, s.name])), [subjects]);
  const curriculumSubject = useMemo(
    () => new Map((curriculums?.data ?? []).map((c) => [c.id, subjectNames.get(c.subject_id) ?? c.subject_id])),
    [curriculums, subjectNames]
  );

  const columns: DataTableColumn<ScheduleEntry>[] = [
    { key: 'timetable', header: 'Emploi du temps', render: (e) => timetableNames.get(e.timetable_id) ?? '—' },
    {
      key: 'subject',
      header: 'Matière',
      render: (e) => (e.teaching_assignment ? curriculumSubject.get(e.teaching_assignment.curriculum_id) ?? '—' : '—'),
    },
    {
      key: 'teacher',
      header: 'Enseignant',
      render: (e) => (e.teaching_assignment ? teacherNames.get(e.teaching_assignment.teacher_id) ?? '—' : '—'),
    },
    { key: 'day', header: 'Jour', render: (e) => DAY_LABELS[e.day_of_week] },
    {
      key: 'time',
      header: 'Horaire',
      // Postgres returns TIME columns as HH:MM:SS; the form only ever
      // submits HH:MM, so trimming to 5 chars here just normalizes the
      // display back to what was entered.
      render: (e) => `${e.start_time.slice(0, 5)} - ${e.end_time.slice(0, 5)}`,
    },
    { key: 'room', header: 'Salle', render: (e) => e.room ?? '—' },
    {
      key: 'status',
      header: 'Statut',
      render: (e) => <StatusBadge label={SCHEDULE_ENTRY_STATUS_LABELS[e.status]} tone={STATUS_TONE[e.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (e) => (
        <Can permission="schedule-entry.manage">
          <div className="flex justify-end gap-1">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => generateSessions.mutate(e.id)}
              title="Générer les séances"
              disabled={generateSessions.isPending}
            >
              <RiPlayCircleLine className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => regenerate.mutate(e.id)}
              title="Régénérer les séances futures"
              disabled={regenerate.isPending}
            >
              <RiRefreshLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setArchiving(e)} title="Archiver">
              <RiArchiveLine className="h-4 w-4" />
            </Button>
          </div>
        </Can>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Créneaux horaires"
        description="Cours prévus et récurrents. Générez les séances réelles une fois le créneau créé."
        action={
          <Can permission="schedule-entry.manage">
            <Button onClick={() => setCreating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouveau créneau
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(e) => e.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucun créneau"
        emptyDescription="Créez un créneau pour un emploi du temps."
      />

      <ScheduleEntryFormSheet open={creating} onOpenChange={setCreating} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver ce créneau ?"
        description="Les séances déjà générées restent conservées."
        confirmLabel="Archiver"
        destructive
        isConfirming={archive.isPending}
        onConfirm={async () => {
          if (archiving) {
            await archive.mutateAsync(archiving.id);
            setArchiving(null);
          }
        }}
      />
    </div>
  );
}
