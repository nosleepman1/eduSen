import { useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiPlayLine, RiStopLine, RiAddLine, RiMagicLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useAcademicYearList } from '@/hooks/useAcademicYears';
import {
  useAcademicPeriodList,
  useActivateAcademicPeriod,
  useCloseAcademicPeriod,
  useArchiveAcademicPeriod,
} from '@/hooks/useAcademicPeriods';
import { AcademicPeriodFormSheet } from './AcademicPeriodFormSheet';
import { GeneratePeriodsDialog } from './GeneratePeriodsDialog';
import { ACADEMIC_PERIOD_STATUS_LABELS, type AcademicPeriod, type AcademicPeriodStatus } from '@/types/academicPeriod.types';

const STATUS_TONE: Record<AcademicPeriodStatus, Tone> = {
  DRAFT: 'neutral',
  ACTIVE: 'success',
  CLOSED: 'info',
  ARCHIVED: 'danger',
};

export default function AcademicPeriodPage() {
  const [page, setPage] = useState(1);
  const [yearFilter, setYearFilter] = useState('');
  const [editing, setEditing] = useState<AcademicPeriod | null | 'create'>(null);
  const [generatingFor, setGeneratingFor] = useState<string | null>(null);
  const [archiving, setArchiving] = useState<AcademicPeriod | null>(null);

  const { data: years } = useAcademicYearList(1);
  const { data, isLoading } = useAcademicPeriodList(page, yearFilter);
  const activate = useActivateAcademicPeriod();
  const close = useCloseAcademicPeriod();
  const archive = useArchiveAcademicPeriod();

  const selectedYear = (years?.data ?? []).find((y) => y.id === yearFilter);

  const columns: DataTableColumn<AcademicPeriod>[] = [
    { key: 'name', header: 'Période', render: (p) => p.name },
    { key: 'start_date', header: 'Début', render: (p) => p.start_date },
    { key: 'end_date', header: 'Fin', render: (p) => p.end_date },
    {
      key: 'status',
      header: 'Statut',
      render: (p) => <StatusBadge label={ACADEMIC_PERIOD_STATUS_LABELS[p.status]} tone={STATUS_TONE[p.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (p) => (
        <Can permission="academic-period.manage">
          <div className="flex justify-end gap-1">
            {p.status !== 'ARCHIVED' && p.status !== 'ACTIVE' && (
              <Button variant="ghost" size="sm" onClick={() => setEditing(p)} title="Modifier">
                <RiPencilLine className="h-4 w-4" />
              </Button>
            )}
            {(p.status === 'DRAFT' || p.status === 'CLOSED') && (
              <Button variant="ghost" size="sm" onClick={() => activate.mutate(p.id)} title="Activer">
                <RiPlayLine className="h-4 w-4" />
              </Button>
            )}
            {p.status === 'ACTIVE' && (
              <Button variant="ghost" size="sm" onClick={() => close.mutate(p.id)} title="Clôturer">
                <RiStopLine className="h-4 w-4" />
              </Button>
            )}
            {p.status !== 'ACTIVE' && p.status !== 'ARCHIVED' && (
              <Button variant="ghost" size="sm" onClick={() => setArchiving(p)} title="Archiver">
                <RiArchiveLine className="h-4 w-4" />
              </Button>
            )}
          </div>
        </Can>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Périodes académiques"
        description="Une seule période peut être active par année scolaire."
        action={
          <div className="flex gap-2">
            <Can permission="academic-year.manage">
              <Button
                variant="outline"
                disabled={!yearFilter}
                onClick={() => setGeneratingFor(yearFilter)}
                title={yearFilter ? undefined : "Sélectionnez d'abord une année ci-dessous"}
              >
                <RiMagicLine className="mr-1 h-4 w-4" />
                Générer
              </Button>
            </Can>
            <Can permission="academic-period.manage">
              <Button onClick={() => setEditing('create')}>
                <RiAddLine className="mr-1 h-4 w-4" />
                Nouvelle période
              </Button>
            </Can>
          </div>
        }
      />

      <div className="flex items-center gap-2">
        <label htmlFor="year-filter" className="text-sm text-muted-foreground">
          Année :
        </label>
        <select
          id="year-filter"
          value={yearFilter}
          onChange={(e) => {
            setYearFilter(e.target.value);
            setPage(1);
          }}
          className="flex h-9 max-w-xs rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
        >
          <option value="">Toutes les années</option>
          {(years?.data ?? []).map((y) => (
            <option key={y.id} value={y.id}>
              {y.name}
            </option>
          ))}
        </select>
      </div>

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(p) => p.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune période académique"
        emptyDescription="Générez un découpage par défaut ou créez une période manuellement."
      />

      <AcademicPeriodFormSheet
        period={editing}
        defaultAcademicYearId={yearFilter || undefined}
        onOpenChange={(open) => !open && setEditing(null)}
      />

      <GeneratePeriodsDialog
        academicYearId={generatingFor}
        academicYearName={selectedYear?.name ?? ''}
        onOpenChange={(open) => !open && setGeneratingFor(null)}
      />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette période ?"
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
