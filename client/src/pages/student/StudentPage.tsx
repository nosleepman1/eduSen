import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine, RiParentLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useStudentList, useArchiveStudent } from '@/hooks/useStudents';
import { StudentFormSheet } from './StudentFormSheet';
import { LinkGuardianSheet } from './LinkGuardianSheet';
import { STUDENT_STATUS_LABELS, type Student, type StudentStatus } from '@/types/student.types';

const STATUS_TONE: Record<StudentStatus, 'success' | 'warning' | 'danger' | 'info'> = {
  ACTIVE: 'success',
  TRANSFERRED: 'info',
  GRADUATED: 'success',
  EXPELLED: 'danger',
};

export default function StudentPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [editing, setEditing] = useState<Student | null | 'create'>(null);
  const [managingGuardians, setManagingGuardians] = useState<Student | null>(null);
  const [archiving, setArchiving] = useState<Student | null>(null);

  const { data, isLoading } = useStudentList(page, search);
  const archive = useArchiveStudent();

  const columns: DataTableColumn<Student>[] = [
    { key: 'full_name', header: 'Nom', render: (s) => s.full_name },
    { key: 'student_number', header: 'Matricule', render: (s) => s.student_number },
    { key: 'birth_date', header: 'Naissance', render: (s) => s.birth_date ?? '—' },
    {
      key: 'status',
      header: 'Statut',
      render: (s) =>
        s.status ? <StatusBadge label={STUDENT_STATUS_LABELS[s.status]} tone={STATUS_TONE[s.status]} /> : '—',
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (s) => (
        <div className="flex justify-end gap-1">
          <Can permission="student.create">
            <Button variant="ghost" size="sm" onClick={() => setManagingGuardians(s)} title="Tuteurs">
              <RiParentLine className="h-4 w-4" />
            </Button>
          </Can>
          <Can permission="student.update">
            <Button variant="ghost" size="sm" onClick={() => setEditing(s)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
          </Can>
          <Can permission="student.delete">
            <Button variant="ghost" size="sm" onClick={() => setArchiving(s)} title="Archiver">
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
        title="Élèves"
        description="Élèves inscrits dans l'établissement."
        action={
          <Can permission="student.create">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvel élève
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(s) => s.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        onSearchChange={(value) => {
          setSearch(value);
          setPage(1);
        }}
        searchPlaceholder="Rechercher un nom ou prénom..."
        emptyTitle="Aucun élève"
        emptyDescription="Ajoutez un élève pour le voir apparaître ici."
      />

      <StudentFormSheet student={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <LinkGuardianSheet
        studentId={managingGuardians?.id ?? null}
        studentName={managingGuardians?.full_name ?? ''}
        onOpenChange={(open) => !open && setManagingGuardians(null)}
      />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cet élève ?"
        description={archiving ? `${archiving.full_name} sera archivé. Cette action peut être annulée par un administrateur.` : ''}
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
