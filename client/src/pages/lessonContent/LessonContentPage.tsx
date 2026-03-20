import { useState } from 'react';
import { RiAddLine, RiPencilLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useLessonContentList, useArchiveLessonContent } from '@/hooks/useLessonContents';
import { LessonContentFormSheet } from './LessonContentFormSheet';
import type { LessonContent } from '@/types/lessonContent.types';

export default function LessonContentPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<LessonContent | null | 'create'>(null);
  const [archiving, setArchiving] = useState<LessonContent | null>(null);

  const { data, isLoading } = useLessonContentList(page);
  const archive = useArchiveLessonContent();

  const columns: DataTableColumn<LessonContent>[] = [
    { key: 'title', header: 'Titre', render: (c) => c.title },
    { key: 'progress', header: 'Progression', render: (c) => c.progress_number ?? '—' },
    { key: 'description', header: 'Description', render: (c) => c.description ?? '—' },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (c) => (
        <Can permission="lesson-content.manage">
          <div className="flex justify-end gap-1">
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
        title="Contenus de cours"
        description="Ce qui a réellement été enseigné lors de chaque séance."
        action={
          <Can permission="lesson-content.manage">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouveau contenu
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
        emptyTitle="Aucun contenu"
        emptyDescription="Renseignez le contenu d'une séance en cours ou terminée."
      />

      <LessonContentFormSheet content={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver ce contenu ?"
        description={archiving ? `${archiving.title} sera archivé.` : ''}
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
