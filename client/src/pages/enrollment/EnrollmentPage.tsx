import { useState } from 'react';
import { RiAddLine, RiArrowLeftRightLine, RiLogoutBoxLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useEnrollmentList, useWithdrawEnrollment, useArchiveEnrollment } from '@/hooks/useEnrollments';
import { EnrollmentFormSheet } from './EnrollmentFormSheet';
import { TransferEnrollmentDialog } from './TransferEnrollmentDialog';
import { ENROLLMENT_STATUS_LABELS, type Enrollment, type EnrollmentStatus } from '@/types/enrollment.types';

const STATUS_TONE: Record<EnrollmentStatus, Tone> = {
  ACTIVE: 'success',
  TRANSFERRED: 'info',
  WITHDRAWN: 'neutral',
  ARCHIVED: 'danger',
};

export default function EnrollmentPage() {
  const [page, setPage] = useState(1);
  const [creating, setCreating] = useState(false);
  const [transferring, setTransferring] = useState<Enrollment | null>(null);
  const [withdrawing, setWithdrawing] = useState<Enrollment | null>(null);
  const [archiving, setArchiving] = useState<Enrollment | null>(null);

  const { data, isLoading } = useEnrollmentList(page);
  const withdraw = useWithdrawEnrollment();
  const archive = useArchiveEnrollment();

  const columns: DataTableColumn<Enrollment>[] = [
    { key: 'registration_number', header: "N° d'inscription", render: (e) => e.registration_number },
    { key: 'student', header: 'Élève', render: (e) => e.student?.full_name ?? '—' },
    { key: 'classroom', header: 'Classe', render: (e) => e.classroom?.name ?? '—' },
    { key: 'enrolled_at', header: 'Inscrit le', render: (e) => e.enrolled_at },
    {
      key: 'status',
      header: 'Statut',
      render: (e) => <StatusBadge label={ENROLLMENT_STATUS_LABELS[e.status]} tone={STATUS_TONE[e.status]} />,
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (e) => (
        <Can permission="enrollment.manage">
          <div className="flex justify-end gap-1">
            {e.status === 'ACTIVE' && (
              <>
                <Button variant="ghost" size="sm" onClick={() => setTransferring(e)} title="Transférer">
                  <RiArrowLeftRightLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => setWithdrawing(e)} title="Clôturer">
                  <RiLogoutBoxLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => setArchiving(e)} title="Archiver">
                  <RiArchiveLine className="h-4 w-4" />
                </Button>
              </>
            )}
            {/* No ARCHIVED branch: EnrollmentController::index() never
                returns soft-deleted rows, so an archived enrollment can't
                appear in this list — a Restore button here would be
                permanently unreachable. The backend restore endpoint still
                exists (useRestoreEnrollment), just with no client affordance
                yet, same as every other domain in this app. */}
          </div>
        </Can>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Inscriptions"
        description="Inscriptions des élèves dans les classes : transferts, clôtures et historique."
        action={
          <Can permission="enrollment.manage">
            <Button onClick={() => setCreating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouvelle inscription
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(e) => e.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune inscription"
        emptyDescription="Inscrivez un élève dans une classe pour commencer."
      />

      <EnrollmentFormSheet open={creating} onOpenChange={setCreating} />

      <TransferEnrollmentDialog enrollment={transferring} onOpenChange={(open) => !open && setTransferring(null)} />

      <ConfirmDialog
        open={withdrawing !== null}
        onOpenChange={(open) => !open && setWithdrawing(null)}
        title="Clôturer cette inscription ?"
        description={withdrawing ? `${withdrawing.student?.full_name} quittera la classe ${withdrawing.classroom?.name}.` : ''}
        confirmLabel="Clôturer"
        destructive
        isConfirming={withdraw.isPending}
        onConfirm={async () => {
          if (withdrawing) {
            await withdraw.mutateAsync({ id: withdrawing.id, payload: {} });
            setWithdrawing(null);
          }
        }}
      />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette inscription ?"
        description={archiving ? `L'inscription de ${archiving.student?.full_name} sera archivée.` : ''}
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
