import { useMemo, useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine, RiFileCopyLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useLevelList } from '@/hooks/useLevels';
import { useSubjectList } from '@/hooks/useSubjects';
import { useCurriculumList, useArchiveCurriculum } from '@/hooks/useCurriculums';
import { CurriculumFormSheet } from './CurriculumFormSheet';
import { DuplicateCurriculumDialog } from './DuplicateCurriculumDialog';
import type { Curriculum } from '@/types/curriculum.types';

export default function CurriculumPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<Curriculum | null | 'create'>(null);
  const [duplicating, setDuplicating] = useState(false);
  const [archiving, setArchiving] = useState<Curriculum | null>(null);

  const { data, isLoading } = useCurriculumList(page);
  // Level and Subject have no search either — first page only, same
  // documented limitation as their own screens. Used here purely to resolve
  // id -> name for display, since CurriculumResource exposes ids only.
  const { data: levels } = useLevelList(1);
  const { data: subjects } = useSubjectList();
  const archive = useArchiveCurriculum();

  const levelNames = useMemo(
    () => new Map((levels?.data ?? []).map((l) => [l.id, l.name])),
    [levels]
  );
  const subjectNames = useMemo(
    () => new Map((subjects?.data ?? []).map((s) => [s.id, s.name])),
    [subjects]
  );

  const columns: DataTableColumn<Curriculum>[] = [
    { key: 'level', header: 'Niveau', render: (c) => levelNames.get(c.level_id) ?? c.level_id },
    { key: 'subject', header: 'Matière', render: (c) => subjectNames.get(c.subject_id) ?? c.subject_id },
    { key: 'coefficient', header: 'Coefficient', render: (c) => c.coefficient },
    { key: 'weekly_hours', header: 'Heures / semaine', render: (c) => c.weekly_hours },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (c) => (
        <Can permission="curriculum.manage">
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
        title="Programmes pédagogiques"
        description="Matières enseignées par niveau, avec coefficient et volume horaire."
        action={
          <Can permission="curriculum.manage">
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => setDuplicating(true)}>
                <RiFileCopyLine className="mr-1 h-4 w-4" />
                Dupliquer vers une année
              </Button>
              <Button onClick={() => setEditing('create')}>
                <RiAddLine className="mr-1 h-4 w-4" />
                Nouvelle ligne
              </Button>
            </div>
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
        emptyTitle="Aucune ligne de programme"
        emptyDescription="Ajoutez une matière à un niveau, ou dupliquez le programme d'une année existante."
      />

      <CurriculumFormSheet curriculum={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <DuplicateCurriculumDialog open={duplicating} onOpenChange={setDuplicating} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette ligne de programme ?"
        description={
          archiving
            ? `${subjectNames.get(archiving.subject_id) ?? archiving.subject_id} pour ${levelNames.get(archiving.level_id) ?? archiving.level_id} sera archivée.`
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
