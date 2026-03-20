import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useSpecializationList, useArchiveSpecialization } from '@/hooks/useSpecializations';
import { SpecializationFormSheet } from './SpecializationFormSheet';
import type { Specialization } from '@/types/specialization.types';

export default function SpecializationPage() {
  const [editing, setEditing] = useState<Specialization | null | 'create'>(null);
  const [archiving, setArchiving] = useState<Specialization | null>(null);

  const { data, isLoading } = useSpecializationList();
  const archive = useArchiveSpecialization();

  const columns: DataTableColumn<Specialization>[] = [
    { key: 'name', header: 'Nom', render: (s) => s.name },
    { key: 'code', header: 'Code', render: (s) => s.code },
    { key: 'description', header: 'Description', render: (s) => s.description ?? '—' },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (s) => (
        <Can permission="specialization.manage">
          <div className="flex justify-end gap-1">
            <Button variant="ghost" size="sm" onClick={() => setEditing(s)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setArchiving(s)} title="Archiver">
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
        title="Spécialisations"
        description="Filières optionnelles pouvant être associées à un niveau (ex. Scientifique, Littéraire)."
        action={
          <Can permission="specialization.manage">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvelle spécialisation
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(s) => s.id}
        isLoading={isLoading}
        emptyTitle="Aucune spécialisation"
        emptyDescription="Créez une spécialisation pour pouvoir l'associer à un niveau."
      />

      <SpecializationFormSheet specialization={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette spécialisation ?"
        description={archiving ? `${archiving.name} sera archivée.` : ''}
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
