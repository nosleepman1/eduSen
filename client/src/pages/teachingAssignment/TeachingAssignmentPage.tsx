import { useMemo, useState } from 'react';
import { RiAddLine, RiUserSettingsLine, RiStopLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useCurriculumList } from '@/hooks/useCurriculums';
import { useSubjectList } from '@/hooks/useSubjects';
import {
  useTeachingAssignmentList,
  useCloseTeachingAssignment,
  useArchiveTeachingAssignment,
} from '@/hooks/useTeachingAssignments';
import { TeachingAssignmentFormSheet } from './TeachingAssignmentFormSheet';
import { ReassignDialog } from './ReassignDialog';
import {
  ROLE_LABELS,
  TEACHING_ASSIGNMENT_STATUS_LABELS,
  type TeachingAssignment,
  type TeachingAssignmentStatus,
} from '@/types/teachingAssignment.types';

const STATUS_TONE: Record<TeachingAssignmentStatus, Tone> = {
  ACTIVE: 'success',
  CLOSED: 'neutral',
};

export default function TeachingAssignmentPage() {
  const [page, setPage] = useState(1);
  const [creating, setCreating] = useState(false);
  const [reassigning, setReassigning] = useState<TeachingAssignment | null>(null);
  const [closing, setClosing] = useState<TeachingAssignment | null>(null);
  const [archiving, setArchiving] = useState<TeachingAssignment | null>(null);

  const { data, isLoading } = useTeachingAssignmentList(page);
  const { data: curriculums } = useCurriculumList(1);
  const { data: subjects } = useSubjectList();
  const close = useCloseTeachingAssignment();
  const archive = useArchiveTeachingAssignment();

  const subjectNames = useMemo(() => new Map((subjects?.data ?? []).map((s) => [s.id, s.name])), [subjects]);
  const curriculumSubject = useMemo(
    () => new Map((curriculums?.data ?? []).map((c) => [c.id, subjectNames.get(c.subject_id) ?? c.subject_id])),
    [curriculums, subjectNames]
  );

  const columns: DataTableColumn<TeachingAssignment>[] = [
    { key: 'classroom', header: 'Classe', render: (a) => a.classroom?.name ?? '—' },
    { key: 'subject', header: 'Matière', render: (a) => curriculumSubject.get(a.curriculum_id) ?? '—' },
    { key: 'teacher', header: 'Enseignant', render: (a) => a.teacher?.full_name ?? '—' },
    { key: 'role', header: 'Rôle', render: (a) => ROLE_LABELS[a.role] },
    {
      key: 'status',
      header: 'Statut',
      render: (a) => <StatusBadge label={TEACHING_ASSIGNMENT_STATUS_LABELS[a.status]} tone={STATUS_TONE[a.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (a) => (
        <Can permission="teaching-assignment.manage">
          <div className="flex justify-end gap-1">
            {a.status === 'ACTIVE' && (
              <>
                <Button variant="ghost" size="sm" onClick={() => setReassigning(a)} title="Réaffecter">
                  <RiUserSettingsLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => setClosing(a)} title="Clôturer">
                  <RiStopLine className="h-4 w-4" />
                </Button>
              </>
            )}
            <Button variant="ghost" size="sm" onClick={() => setArchiving(a)} title="Archiver">
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
        title="Affectations pédagogiques"
        description="Qui enseigne quelle matière, dans quelle classe."
        action={
          <Can permission="teaching-assignment.manage">
            <Button onClick={() => setCreating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvelle affectation
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(a) => a.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune affectation"
        emptyDescription="Affectez un enseignant à une matière pour une classe."
      />

      <TeachingAssignmentFormSheet open={creating} onOpenChange={setCreating} />

      <ReassignDialog assignment={reassigning} onOpenChange={(open) => !open && setReassigning(null)} />

      <ConfirmDialog
        open={closing !== null}
        onOpenChange={(open) => !open && setClosing(null)}
        title="Clôturer cette affectation ?"
        description={closing ? `${closing.teacher?.full_name} ne sera plus affecté à cette matière.` : ''}
        confirmLabel="Clôturer"
        destructive
        isConfirming={close.isPending}
        onConfirm={async () => {
          if (closing) {
            await close.mutateAsync(closing.id);
            setClosing(null);
          }
        }}
      />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette affectation ?"
        description={archiving ? `L'affectation de ${archiving.teacher?.full_name} sera archivée.` : ''}
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
