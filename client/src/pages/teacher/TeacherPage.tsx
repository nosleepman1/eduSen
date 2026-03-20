import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useTeacherList, useArchiveTeacher } from '@/hooks/useTeachers';
import { CreateTeacherSheet } from './CreateTeacherSheet';
import { EditTeacherSheet } from './EditTeacherSheet';
import type { Teacher } from '@/types/teacher.types';

export default function TeacherPage() {
  const [page, setPage] = useState(1);
  const [creating, setCreating] = useState(false);
  const [editing, setEditing] = useState<Teacher | null>(null);
  const [archiving, setArchiving] = useState<Teacher | null>(null);

  const { data, isLoading } = useTeacherList(page);
  const archive = useArchiveTeacher();

  const columns: DataTableColumn<Teacher>[] = [
    { key: 'full_name', header: 'Nom', render: (t) => t.full_name || '—' },
    { key: 'speciality', header: 'Spécialité', render: (t) => t.speciality ?? '—' },
    { key: 'diploma', header: 'Diplôme', render: (t) => t.diploma ?? '—' },
    { key: 'years_experience', header: 'Expérience', render: (t) => (t.years_experience !== null ? `${t.years_experience} an(s)` : '—') },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (t) => (
        <div className="flex justify-end gap-1">
          <Can permission="teacher.update">
            <Button variant="ghost" size="sm" onClick={() => setEditing(t)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
          </Can>
          <Can permission="teacher.delete">
            <Button variant="ghost" size="sm" onClick={() => setArchiving(t)} title="Archiver">
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
        title="Enseignants"
        description="Profils pédagogiques du personnel enseignant."
        action={
          <Can permission="teacher.create">
            <Button onClick={() => setCreating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouveau profil
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
        emptyTitle="Aucun profil enseignant"
        emptyDescription="Créez un profil pour un membre du personnel ayant le rôle Enseignant."
      />

      <CreateTeacherSheet open={creating} onOpenChange={setCreating} existingTeachers={data?.data ?? []} />
      <EditTeacherSheet teacher={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver ce profil enseignant ?"
        description={
          archiving
            ? `Le personnel lié à ${archiving.full_name} sera suspendu et ses rôles désactivés — l'archivage porte sur le dossier du personnel, pas seulement sur le profil enseignant.`
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
