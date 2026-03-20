import { useState } from 'react';
import { RiAddLine, RiPencilLine, RiPlayLine, RiFileCopyLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useTimetableList, useActivateTimetable, useDuplicateTimetable, useArchiveTimetable } from '@/hooks/useTimetables';
import { TimetableFormSheet } from './TimetableFormSheet';
import { TIMETABLE_STATUS_LABELS, type Timetable, type TimetableStatus } from '@/types/timetable.types';

const STATUS_TONE: Record<TimetableStatus, Tone> = {
  DRAFT: 'neutral',
  ACTIVE: 'success',
  ARCHIVED: 'danger',
};

export default function TimetablePage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<Timetable | null | 'create'>(null);
  const [archiving, setArchiving] = useState<Timetable | null>(null);

  const { data, isLoading } = useTimetableList(page);
  const activate = useActivateTimetable();
  const duplicate = useDuplicateTimetable();
  const archive = useArchiveTimetable();

  const columns: DataTableColumn<Timetable>[] = [
    { key: 'name', header: 'Nom', render: (t) => t.name },
    { key: 'classroom', header: 'Classe', render: (t) => t.classroom?.name ?? '—' },
    {
      key: 'status',
      header: 'Statut',
      render: (t) => <StatusBadge label={TIMETABLE_STATUS_LABELS[t.status]} tone={STATUS_TONE[t.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (t) => (
        <Can permission="timetable.manage">
          <div className="flex justify-end gap-1">
            {t.status !== 'ACTIVE' && (
              <Button variant="ghost" size="sm" onClick={() => activate.mutate(t.id)} title="Activer">
                <RiPlayLine className="h-4 w-4" />
              </Button>
            )}
            <Button variant="ghost" size="sm" onClick={() => duplicate.mutate(t.id)} title="Dupliquer">
              <RiFileCopyLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setEditing(t)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setArchiving(t)} title="Archiver">
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
        title="Emplois du temps"
        description="Un seul emploi du temps actif par classe. Le dupliquer copie le conteneur, pas encore les créneaux."
        action={
          <Can permission="timetable.manage">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvel emploi du temps
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(t) => t.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucun emploi du temps"
        emptyDescription="Créez un emploi du temps pour une classe."
      />

      <TimetableFormSheet timetable={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cet emploi du temps ?"
        description={archiving ? `${archiving.name} sera archivé.` : ''}
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
