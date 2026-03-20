import { useState } from 'react';
import { RiPlayLine, RiStopLine, RiCloseLine, RiTimeLine, RiCheckDoubleLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useLessonSessionList, useStartSession, useFinishSession, usePostponeSession } from '@/hooks/useLessonSessions';
import { CancelSessionDialog } from './CancelSessionDialog';
import { ValidateSessionDialog } from './ValidateSessionDialog';
import { LESSON_SESSION_STATUS_LABELS, type LessonSession, type LessonSessionStatus } from '@/types/lessonSession.types';

const STATUS_TONE: Record<LessonSessionStatus, Tone> = {
  SCHEDULED: 'neutral',
  PENDING_START: 'warning',
  IN_PROGRESS: 'info',
  PENDING_VALIDATION: 'warning',
  COMPLETED: 'success',
  CANCELLED: 'danger',
  POSTPONED: 'danger',
  ARCHIVED: 'neutral',
};

const CANCELLABLE: LessonSessionStatus[] = ['SCHEDULED', 'PENDING_START', 'PENDING_VALIDATION'];

export default function LessonSessionPage() {
  const [page, setPage] = useState(1);
  const [cancelling, setCancelling] = useState<LessonSession | null>(null);
  const [validating, setValidating] = useState<LessonSession | null>(null);
  const [postponing, setPostponing] = useState<LessonSession | null>(null);

  const { data, isLoading } = useLessonSessionList(page);
  const start = useStartSession();
  const finish = useFinishSession();
  const postpone = usePostponeSession();

  const columns: DataTableColumn<LessonSession>[] = [
    { key: 'session_date', header: 'Date', render: (s) => s.session_date },
    {
      key: 'time',
      header: 'Horaire prévu',
      render: (s) => `${s.scheduled_start_time.slice(0, 5)} - ${s.scheduled_end_time.slice(0, 5)}`,
    },
    {
      key: 'status',
      header: 'Statut',
      render: (s) => <StatusBadge label={LESSON_SESSION_STATUS_LABELS[s.status]} tone={STATUS_TONE[s.status]} />,
    },
    {
      key: 'reason',
      header: 'Motif',
      render: (s) => s.cancellation_reason ?? '—',
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (s) => (
        <Can permission="lesson-session.manage">
          <div className="flex justify-end gap-1">
            {(s.status === 'SCHEDULED' || s.status === 'PENDING_START') && (
              <Button variant="ghost" size="sm" onClick={() => start.mutate({ id: s.id, payload: undefined })} title="Démarrer">
                <RiPlayLine className="h-4 w-4" />
              </Button>
            )}
            {s.status === 'IN_PROGRESS' && (
              <Button variant="ghost" size="sm" onClick={() => finish.mutate({ id: s.id, payload: undefined })} title="Terminer">
                <RiStopLine className="h-4 w-4" />
              </Button>
            )}
            {s.status === 'PENDING_VALIDATION' && (
              <Button variant="ghost" size="sm" onClick={() => setValidating(s)} title="Valider administrativement">
                <RiCheckDoubleLine className="h-4 w-4" />
              </Button>
            )}
            {CANCELLABLE.includes(s.status) && (
              <>
                <Button variant="ghost" size="sm" onClick={() => setPostponing(s)} title="Reporter">
                  <RiTimeLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" onClick={() => setCancelling(s)} title="Annuler">
                  <RiCloseLine className="h-4 w-4" />
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
        title="Séances de cours"
        description="Séances réelles générées à partir des créneaux. Le statut avance automatiquement selon l'heure, mais seulement à chaque rechargement — pas de tâche planifiée en arrière-plan."
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(s) => s.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune séance"
        emptyDescription="Générez des séances depuis un créneau horaire."
      />

      <CancelSessionDialog session={cancelling} onOpenChange={(open) => !open && setCancelling(null)} />

      <ValidateSessionDialog session={validating} onOpenChange={(open) => !open && setValidating(null)} />

      <ConfirmDialog
        open={postponing !== null}
        onOpenChange={(open) => !open && setPostponing(null)}
        title="Reporter cette séance ?"
        description="Une nouvelle séance pourra être créée séparément pour la remplacer."
        confirmLabel="Reporter"
        destructive
        isConfirming={postpone.isPending}
        onConfirm={async () => {
          if (postponing) {
            await postpone.mutateAsync({ id: postponing.id, payload: {} });
            setPostponing(null);
          }
        }}
      />
    </div>
  );
}
