import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine, RiPlayLine, RiStopLine, RiUserSettingsLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useClassroomList, useOpenClassroom, useCloseClassroom, useArchiveClassroom } from '@/hooks/useClassrooms';
import { ClassroomFormSheet } from './ClassroomFormSheet';
import { AssignTeacherDialog } from './AssignTeacherDialog';
import { CLASSROOM_STATUS_LABELS, type Classroom, type ClassroomStatus } from '@/types/classroom.types';

const STATUS_TONE: Record<ClassroomStatus, Tone> = {
  DRAFT: 'neutral',
  ACTIVE: 'success',
  CLOSED: 'info',
  ARCHIVED: 'danger',
};

export default function ClassroomPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<Classroom | null | 'create'>(null);
  const [assigning, setAssigning] = useState<Classroom | null>(null);
  const [archiving, setArchiving] = useState<Classroom | null>(null);

  const { data, isLoading } = useClassroomList(page);
  const open = useOpenClassroom();
  const close = useCloseClassroom();
  const archive = useArchiveClassroom();

  const columns: DataTableColumn<Classroom>[] = [
    { key: 'full_name', header: 'Classe', render: (c) => c.full_name },
    { key: 'code', header: 'Code', render: (c) => c.code },
    { key: 'capacity', header: 'Capacité', render: (c) => c.capacity },
    {
      key: 'main_teacher',
      header: 'Professeur principal',
      render: (c) => (c.main_teacher_id ? <StatusBadge label="Assigné" tone="success" /> : <StatusBadge label="Aucun" tone="neutral" />),
    },
    {
      key: 'status',
      header: 'Statut',
      render: (c) => <StatusBadge label={CLASSROOM_STATUS_LABELS[c.status]} tone={STATUS_TONE[c.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (c) => (
        <Can permission="classroom.manage">
          <div className="flex justify-end gap-1">
            {c.status !== 'ACTIVE' && (
              <Button variant="ghost" size="sm" onClick={() => open.mutate(c.id)} title="Ouvrir">
                <RiPlayLine className="h-4 w-4" />
              </Button>
            )}
            {c.status === 'ACTIVE' && (
              <Button variant="ghost" size="sm" onClick={() => close.mutate(c.id)} title="Fermer">
                <RiStopLine className="h-4 w-4" />
              </Button>
            )}
            <Button variant="ghost" size="sm" onClick={() => setAssigning(c)} title="Professeur principal">
              <RiUserSettingsLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setEditing(c)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setArchiving(c)} title="Archiver">
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
        title="Classes"
        description="Classes rattachées à un niveau, avec leur capacité et leur professeur principal."
        action={
          <Can permission="classroom.manage">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvelle classe
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(c) => c.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune classe"
        emptyDescription="Créez une classe pour un niveau existant."
      />

      <ClassroomFormSheet classroom={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <AssignTeacherDialog classroom={assigning} onOpenChange={(open) => !open && setAssigning(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette classe ?"
        description={archiving ? `${archiving.full_name} sera archivée.` : ''}
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
