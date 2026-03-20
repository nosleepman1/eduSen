import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiPlayLine, RiStopLine, RiRestartLine, RiAddLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import {
  useAcademicYearList,
  useActivateAcademicYear,
  useCloseAcademicYear,
  useReopenAcademicYear,
  useArchiveAcademicYear,
} from '@/hooks/useAcademicYears';
import { AcademicYearFormSheet } from './AcademicYearFormSheet';
import { ACADEMIC_YEAR_STATUS_LABELS, type AcademicYear, type AcademicYearStatus } from '@/types/academicYear.types';

const STATUS_TONE: Record<AcademicYearStatus, Tone> = {
  DRAFT: 'neutral',
  ACTIVE: 'success',
  CLOSED: 'info',
  ARCHIVED: 'danger',
};

export default function AcademicYearPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<AcademicYear | null | 'create'>(null);
  const [archiving, setArchiving] = useState<AcademicYear | null>(null);

  const { data, isLoading } = useAcademicYearList(page);
  const activate = useActivateAcademicYear();
  const close = useCloseAcademicYear();
  const reopen = useReopenAcademicYear();
  const archive = useArchiveAcademicYear();

  const columns: DataTableColumn<AcademicYear>[] = [
    { key: 'name', header: 'Année', render: (y) => y.name },
    { key: 'start_date', header: 'Début', render: (y) => y.start_date },
    { key: 'end_date', header: 'Fin', render: (y) => y.end_date },
    {
      key: 'status',
      header: 'Statut',
      render: (y) => <StatusBadge label={ACADEMIC_YEAR_STATUS_LABELS[y.status]} tone={STATUS_TONE[y.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (y) => (
        <Can permission="academic-year.manage">
          <div className="flex justify-end gap-1">
            {y.status === 'DRAFT' && (
              <>
                <Button variant="ghost" size="sm" onClick={() => setEditing(y)} title="Modifier">
                  <RiPencilLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => activate.mutate(y.id)} title="Activer">
                  <RiPlayLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => setArchiving(y)} title="Archiver">
                  <RiArchiveLine className="h-4 w-4" />
                </Button>
              </>
            )}
            {y.status === 'ACTIVE' && (
              <>
                <Button variant="ghost" size="sm" onClick={() => setEditing(y)} title="Modifier">
                  <RiPencilLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => close.mutate(y.id)} title="Clôturer">
                  <RiStopLine className="h-4 w-4" />
                </Button>
              </>
            )}
            {y.status === 'CLOSED' && (
              <>
                <Button variant="ghost" size="sm" onClick={() => reopen.mutate(y.id)} title="Réouvrir">
                  <RiRestartLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => setArchiving(y)} title="Archiver">
                  <RiArchiveLine className="h-4 w-4" />
                </Button>
              </>
            )}
          </div>
        </Can>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Années scolaires"
        description="Une seule année peut être active à la fois ; l'activer en ferme automatiquement toute autre."
        action={
          <Can permission="academic-year.manage">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvelle année
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(y) => y.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune année scolaire"
        emptyDescription="Créez la première année scolaire de l'établissement."
      />

      <AcademicYearFormSheet year={editing} onOpenChange={(open) => !open && setEditing(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette année scolaire ?"
        description={
          archiving
            ? `${archiving.name} sera archivée. Aucun écran ne permet actuellement de consulter les années archivées ni de les restaurer depuis l'interface.`
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
