import { useMemo, useState } from 'react';
import { RiAddLine, RiGroupLine, RiCheckLine, RiSendPlaneLine, RiLockLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useEnrollmentList } from '@/hooks/useEnrollments';
import { useAcademicPeriodList } from '@/hooks/useAcademicPeriods';
import { useBulletinList, usePublishBulletin, useLockBulletin, useArchiveBulletin } from '@/hooks/useBulletins';
import { GenerateBulletinDialog } from './GenerateBulletinDialog';
import { GenerateClassBulletinsDialog } from './GenerateClassBulletinsDialog';
import { ValidateBulletinDialog } from './ValidateBulletinDialog';
import { BULLETIN_STATUS_LABELS, type Bulletin, type BulletinStatus } from '@/types/bulletin.types';

const STATUS_TONE: Record<BulletinStatus, Tone> = {
  DRAFT: 'neutral',
  VALIDATED: 'info',
  PUBLISHED: 'success',
  LOCKED: 'warning',
  ARCHIVED: 'danger',
};

export default function BulletinPage() {
  const [page, setPage] = useState(1);
  const [generating, setGenerating] = useState(false);
  const [generatingClass, setGeneratingClass] = useState(false);
  const [validating, setValidating] = useState<Bulletin | null>(null);
  const [archiving, setArchiving] = useState<Bulletin | null>(null);

  const { data, isLoading } = useBulletinList(page);
  const { data: enrollments } = useEnrollmentList(1);
  const { data: periods } = useAcademicPeriodList(1);
  const publish = usePublishBulletin();
  const lock = useLockBulletin();
  const archive = useArchiveBulletin();

  const studentNames = useMemo(
    () => new Map((enrollments?.data ?? []).map((e) => [e.id, e.student?.full_name ?? e.id])),
    [enrollments]
  );
  const periodNames = useMemo(() => new Map((periods?.data ?? []).map((p) => [p.id, p.name])), [periods]);

  const columns: DataTableColumn<Bulletin>[] = [
    { key: 'student', header: 'Élève', render: (b) => studentNames.get(b.enrollment_id) ?? '—' },
    { key: 'period', header: 'Période', render: (b) => periodNames.get(b.academic_period_id) ?? '—' },
    { key: 'average', header: 'Moyenne générale', render: (b) => b.general_average ?? '—' },
    {
      key: 'rank',
      header: 'Rang',
      render: (b) => (b.class_rank && b.total_students ? `${b.class_rank} / ${b.total_students}` : '—'),
    },
    { key: 'mention', header: 'Mention', render: (b) => b.mention ?? '—' },
    {
      key: 'status',
      header: 'Statut',
      render: (b) => <StatusBadge label={BULLETIN_STATUS_LABELS[b.status]} tone={STATUS_TONE[b.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (b) => (
        <Can permission="bulletin.manage">
          <div className="flex justify-end gap-1">
            {b.status === 'DRAFT' && (
              <Button variant="ghost" size="sm" onClick={() => setValidating(b)} title="Valider">
                <RiCheckLine className="h-4 w-4" />
              </Button>
            )}
            {b.status === 'VALIDATED' && (
              <Button variant="ghost" size="sm" onClick={() => publish.mutate(b.id)} title="Publier">
                <RiSendPlaneLine className="h-4 w-4" />
              </Button>
            )}
            {b.status === 'PUBLISHED' && (
              <Button variant="ghost" size="sm" onClick={() => lock.mutate(b.id)} title="Verrouiller">
                <RiLockLine className="h-4 w-4" />
              </Button>
            )}
            {b.status !== 'ARCHIVED' && (
              <Button variant="ghost" size="sm" onClick={() => setArchiving(b)} title="Archiver">
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
        title="Bulletins"
        description="Un bulletin par élève et par période, calculé à partir des moyennes de matières."
        action={
          <Can permission="bulletin.manage">
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => setGeneratingClass(true)}>
                <RiGroupLine className="mr-1 h-4 w-4" />
                Générer pour une classe
              </Button>
              <Button onClick={() => setGenerating(true)}>
                <RiAddLine className="mr-1 h-4 w-4" />
                Générer un bulletin
              </Button>
            </div>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(b) => b.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucun bulletin"
        emptyDescription="Générez les bulletins d'un élève ou d'une classe pour une période active."
      />

      <GenerateBulletinDialog open={generating} onOpenChange={setGenerating} />

      <GenerateClassBulletinsDialog open={generatingClass} onOpenChange={setGeneratingClass} />

      <ValidateBulletinDialog bulletin={validating} onOpenChange={(open) => !open && setValidating(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver ce bulletin ?"
        description="L'enregistrement sera conservé mais masqué de la liste."
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
