import { useMemo, useState } from 'react';
import { RiAddLine, RiPencilLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useEnrollmentList } from '@/hooks/useEnrollments';
import { useGradeList, useArchiveGrade } from '@/hooks/useGrades';
import { BulkGenerateGradesDialog } from './BulkGenerateGradesDialog';
import { GradeValueDialog } from './GradeValueDialog';
import { GRADE_STATUS_LABELS, type Grade, type GradeStatus } from '@/types/grade.types';

const STATUS_TONE: Record<GradeStatus, Tone> = {
  PENDING: 'neutral',
  GRADED: 'success',
  ABSENT: 'danger',
  DISPENSED: 'info',
};

export default function GradePage() {
  const [page, setPage] = useState(1);
  const [generating, setGenerating] = useState(false);
  const [editing, setEditing] = useState<Grade | null>(null);
  const [archiving, setArchiving] = useState<Grade | null>(null);

  const { data, isLoading } = useGradeList(page);
  const { data: enrollments } = useEnrollmentList(1);
  const archive = useArchiveGrade();

  const studentNames = useMemo(
    () => new Map((enrollments?.data ?? []).map((e) => [e.id, e.student?.full_name ?? e.id])),
    [enrollments]
  );

  const columns: DataTableColumn<Grade>[] = [
    { key: 'student', header: 'Élève', render: (g) => studentNames.get(g.enrollment_id) ?? '—' },
    { key: 'value', header: 'Note', render: (g) => g.value ?? '—' },
    {
      key: 'status',
      header: 'Statut',
      render: (g) => <StatusBadge label={GRADE_STATUS_LABELS[g.status]} tone={STATUS_TONE[g.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (g) => (
        <Can permission="grade.manage">
          <div className="flex justify-end gap-1">
            <Button variant="ghost" size="sm" onClick={() => setEditing(g)} title="Saisir la note">
              <RiPencilLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setArchiving(g)} title="Archiver">
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
        title="Notes"
        description="Une note par élève et par évaluation. Générez-les puis saisissez chaque note."
        action={
          <Can permission="grade.manage">
            <Button onClick={() => setGenerating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Générer les notes
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(g) => g.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune note"
        emptyDescription="Générez les notes pour une évaluation ouverte."
      />

      <BulkGenerateGradesDialog open={generating} onOpenChange={setGenerating} />

      <GradeValueDialog grade={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette note ?"
        description="L'enregistrement sera conservé mais masqué de la liste."
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
