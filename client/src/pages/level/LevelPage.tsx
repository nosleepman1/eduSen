import { useMemo, useState } from 'react';
import { RiPencilLine, RiArchiveLine, RiAddLine, RiArrowUpLine, RiArrowDownLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useAcademicYearList } from '@/hooks/useAcademicYears';
import { useLevelList, useArchiveLevel, useReorderLevels } from '@/hooks/useLevels';
import { LevelFormSheet } from './LevelFormSheet';
import type { Level } from '@/types/level.types';

export default function LevelPage() {
  const [page, setPage] = useState(1);
  const [yearFilter, setYearFilter] = useState('');
  const [editing, setEditing] = useState<Level | null | 'create'>(null);
  const [archiving, setArchiving] = useState<Level | null>(null);

  const { data: years } = useAcademicYearList(1);
  const { data, isLoading } = useLevelList(page);
  const archive = useArchiveLevel();
  const reorder = useReorderLevels();

  // Client-side only: LevelController@index has no academic_year_id filter,
  // so this narrows the already-fetched page rather than asking the server
  // for less. display_order is scoped per year, so without a year selected
  // the "1, 2, 3..." shown mixes unrelated sequences from different years.
  const visibleLevels = useMemo(() => {
    const all = data?.data ?? [];
    const filtered = yearFilter ? all.filter((l) => l.academic_year_id === yearFilter) : all;
    return [...filtered].sort((a, b) => a.display_order - b.display_order);
  }, [data, yearFilter]);

  const move = (level: Level, direction: -1 | 1) => {
    const index = visibleLevels.findIndex((l) => l.id === level.id);
    const swapWith = visibleLevels[index + direction];
    if (!swapWith) return;

    const reordered = [...visibleLevels];
    [reordered[index], reordered[index + direction]] = [reordered[index + direction], reordered[index]];

    reorder.mutate({ academic_year_id: level.academic_year_id, ordered_ids: reordered.map((l) => l.id) });
  };

  const columns: DataTableColumn<Level>[] = [
    { key: 'display_order', header: '#', render: (l) => l.display_order, className: 'w-12' },
    { key: 'name', header: 'Nom', render: (l) => l.name },
    { key: 'code', header: 'Code', render: (l) => l.code },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (l) => (
        <Can permission="level.manage">
          <div className="flex justify-end gap-1">
            {yearFilter && (
              <>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => move(l, -1)}
                  disabled={visibleLevels[0]?.id === l.id || reorder.isPending}
                  title="Monter"
                >
                  <RiArrowUpLine className="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => move(l, 1)}
                  disabled={visibleLevels[visibleLevels.length - 1]?.id === l.id || reorder.isPending}
                  title="Descendre"
                >
                  <RiArrowDownLine className="h-4 w-4" />
                </Button>
              </>
            )}
            <Button variant="ghost" size="sm" onClick={() => setEditing(l)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setArchiving(l)} title="Archiver">
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
        title="Niveaux"
        description="Niveaux scolaires (ex. 6ème, Terminale), ordonnés au sein de chaque année scolaire."
        action={
          <Can permission="level.manage">
            <Button onClick={() => setEditing('create')}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouveau niveau
            </Button>
          </Can>
        }
      />

      <div className="flex items-center gap-2">
        <label htmlFor="year-filter" className="text-sm text-muted-foreground">
          Année :
        </label>
        <select
          id="year-filter"
          value={yearFilter}
          onChange={(e) => setYearFilter(e.target.value)}
          className="flex h-9 max-w-xs rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
        >
          <option value="">Toutes les années (tri désactivé)</option>
          {(years?.data ?? []).map((y) => (
            <option key={y.id} value={y.id}>
              {y.name}
            </option>
          ))}
        </select>
      </div>

      <DataTable
        columns={columns}
        rows={visibleLevels}
        rowKey={(l) => l.id}
        isLoading={isLoading}
        meta={yearFilter ? null : data?.meta ?? null}
        onPageChange={yearFilter ? undefined : setPage}
        emptyTitle="Aucun niveau"
        emptyDescription="Créez un niveau pour une année scolaire."
      />

      <LevelFormSheet
        level={editing}
        defaultAcademicYearId={yearFilter || undefined}
        onOpenChange={(open) => !open && setEditing(null)}
      />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver ce niveau ?"
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
