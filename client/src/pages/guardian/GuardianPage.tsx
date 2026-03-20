import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useGuardianList, useArchiveGuardian } from '@/hooks/useGuardians';
import { GuardianFormSheet } from './GuardianFormSheet';
import type { Guardian } from '@/types/guardian.types';

export default function GuardianPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<Guardian | null | 'create'>(null);
  const [archiving, setArchiving] = useState<Guardian | null>(null);

  const { data, isLoading } = useGuardianList(page);
  const archive = useArchiveGuardian();

  const columns: DataTableColumn<Guardian>[] = [
    { key: 'full_name', header: 'Nom', render: (g) => g.full_name },
    { key: 'phone', header: 'Téléphone', render: (g) => g.phone },
    { key: 'email', header: 'Email', render: (g) => g.email ?? '—' },
    { key: 'profession', header: 'Profession', render: (g) => g.profession ?? '—' },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (g) => (
        <div className="flex justify-end gap-1">
          <Can permission="parent.update">
            <Button variant="ghost" size="sm" onClick={() => setEditing(g)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
          </Can>
          <Can permission="parent.delete">
            <Button variant="ghost" size="sm" onClick={() => setArchiving(g)} title="Archiver">
              <RiArchiveLine className="h-4 w-4" />
            </Button>
          </Can>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Parents"
        description="Tuteurs légaux pouvant être associés aux élèves."
        action={
          <Can permission="parent.create">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouveau parent
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
        emptyTitle="Aucun parent"
        emptyDescription="Ajoutez un parent pour pouvoir l'associer à un élève."
      />

      <GuardianFormSheet guardian={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver ce parent ?"
        description={
          archiving
            ? `${archiving.full_name} sera archivé. Les liens existants avec des élèves ne sont pas rompus.`
            : ''
        }
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
