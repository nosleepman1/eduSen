import { useMemo, useState } from 'react';
import { RiAddLine, RiPlayLine, RiStopLine, RiLockLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useTeachingAssignmentList } from '@/hooks/useTeachingAssignments';
import { useTeacherList } from '@/hooks/useTeachers';
import { useCurriculumList } from '@/hooks/useCurriculums';
import { useSubjectList } from '@/hooks/useSubjects';
import { useEvaluationList, useOpenEvaluation, useCloseEvaluation, useLockEvaluation, useArchiveEvaluation } from '@/hooks/useEvaluations';
import { EvaluationFormSheet } from './EvaluationFormSheet';
import { EVALUATION_TYPE_LABELS, EVALUATION_STATUS_LABELS, type Evaluation, type EvaluationStatus } from '@/types/evaluation.types';

const STATUS_TONE: Record<EvaluationStatus, Tone> = {
  DRAFT: 'neutral',
  OPEN: 'success',
  CLOSED: 'info',
  LOCKED: 'warning',
  ARCHIVED: 'danger',
};

export default function EvaluationPage() {
  const [page, setPage] = useState(1);
  const [creating, setCreating] = useState(false);
  const [archiving, setArchiving] = useState<Evaluation | null>(null);

  const { data, isLoading } = useEvaluationList(page);
  const { data: assignments } = useTeachingAssignmentList(1);
  const { data: teachers } = useTeacherList(1);
  const { data: curriculums } = useCurriculumList(1);
  const { data: subjects } = useSubjectList();
  const open = useOpenEvaluation();
  const close = useCloseEvaluation();
  const lock = useLockEvaluation();
  const archive = useArchiveEvaluation();

  const teacherNames = useMemo(() => new Map((teachers?.data ?? []).map((t) => [t.id, t.full_name])), [teachers]);
  const subjectNames = useMemo(() => new Map((subjects?.data ?? []).map((s) => [s.id, s.name])), [subjects]);
  const curriculumSubject = useMemo(
    () => new Map((curriculums?.data ?? []).map((c) => [c.id, subjectNames.get(c.subject_id) ?? c.subject_id])),
    [curriculums, subjectNames]
  );
  const assignmentsById = useMemo(() => new Map((assignments?.data ?? []).map((a) => [a.id, a])), [assignments]);

  const columns: DataTableColumn<Evaluation>[] = [
    { key: 'title', header: 'Titre', render: (e) => e.title },
    { key: 'type', header: 'Type', render: (e) => EVALUATION_TYPE_LABELS[e.type] },
    {
      key: 'subject',
      header: 'Matière',
      render: (e) => {
        const assignment = assignmentsById.get(e.teaching_assignment_id);
        return assignment ? curriculumSubject.get(assignment.curriculum_id) ?? '—' : '—';
      },
    },
    {
      key: 'teacher',
      header: 'Enseignant',
      render: (e) => {
        const assignment = assignmentsById.get(e.teaching_assignment_id);
        return assignment ? teacherNames.get(assignment.teacher_id) ?? '—' : '—';
      },
    },
    { key: 'date', header: 'Date', render: (e) => e.evaluation_date },
    { key: 'max_score', header: 'Barème', render: (e) => e.max_score },
    {
      key: 'status',
      header: 'Statut',
      render: (e) => <StatusBadge label={EVALUATION_STATUS_LABELS[e.status]} tone={STATUS_TONE[e.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (e) => (
        <Can permission="evaluation.manage">
          <div className="flex justify-end gap-1">
            {e.status === 'DRAFT' && (
              <Button variant="ghost" size="sm" onClick={() => open.mutate(e.id)} title="Ouvrir la saisie">
                <RiPlayLine className="h-4 w-4" />
              </Button>
            )}
            {e.status === 'OPEN' && (
              <Button variant="ghost" size="sm" onClick={() => close.mutate(e.id)} title="Clôturer">
                <RiStopLine className="h-4 w-4" />
              </Button>
            )}
            {e.status === 'CLOSED' && (
              <Button variant="ghost" size="sm" onClick={() => lock.mutate(e.id)} title="Verrouiller">
                <RiLockLine className="h-4 w-4" />
              </Button>
            )}
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
        title="Évaluations"
        description="Devoirs et examens. Ouvrir une évaluation génère automatiquement une note vide pour chaque élève inscrit."
        action={
          <Can permission="evaluation.manage">
            <Button onClick={() => setCreating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvelle évaluation
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
        emptyTitle="Aucune évaluation"
        emptyDescription="Créez un devoir ou un examen pour une période active."
      />

      <EvaluationFormSheet open={creating} onOpenChange={setCreating} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette évaluation ?"
        description={archiving ? `${archiving.title} sera archivée.` : ''}
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
