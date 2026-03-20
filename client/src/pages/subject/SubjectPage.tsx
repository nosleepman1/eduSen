import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useSubjectList, useArchiveSubject } from '@/hooks/useSubjects';
import { SubjectFormSheet } from './SubjectFormSheet';
import type { Subject } from '@/types/subject.types';

export default function SubjectPage() {
  const [editing, setEditing] = useState<Subject | null | 'create'>(null);
  const [archiving, setArchiving] = useState<Subject | null>(null);

  const { data, isLoading } = useSubjectList();
  const archive = useArchiveSubject();

  const columns: DataTableColumn<Subject>[] = [
    { key: 'name', header: 'Nom', render: (s) => s.name },
    { key: 'code', header: 'Code', render: (s) => s.code },
    { key: 'description', header: 'Description', render: (s) => s.description ?? '—' },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (s) => (
        <Can permission="subject.manage">
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
        title="Matières"
        description="Matières enseignées, pouvant être associées à un niveau via un curriculum."
        action={
          <Can permission="subject.manage">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvelle matière
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(s) => s.id}
        isLoading={isLoading}
        emptyTitle="Aucune matière"
        emptyDescription="Créez une matière pour pouvoir l'associer à un niveau."
      />

      <SubjectFormSheet subject={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette matière ?"
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
