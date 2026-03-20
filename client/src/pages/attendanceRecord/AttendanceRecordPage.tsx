import { useMemo, useState } from 'react';
import { RiAddLine, RiCheckLine, RiCloseLine, RiFileTextLine, RiCheckDoubleLine, RiForbidLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useEnrollmentList } from '@/hooks/useEnrollments';
import {
  useAttendanceRecordList,
  useUpdateAttendance,
  useApproveJustification,
  useRejectJustification,
  useArchiveAttendance,
} from '@/hooks/useAttendanceRecords';
import { BulkGenerateDialog } from './BulkGenerateDialog';
import { JustifyDialog } from './JustifyDialog';
import {
  ATTENDANCE_STATUS_LABELS,
  JUSTIFICATION_STATUS_LABELS,
  type AttendanceRecord,
  type AttendanceStatus,
} from '@/types/attendanceRecord.types';

const STATUS_TONE: Record<AttendanceStatus, Tone> = {
  PRESENT: 'success',
  ABSENT: 'danger',
  LATE: 'warning',
  EXCUSED: 'info',
  DISPENSED: 'neutral',
};

export default function AttendanceRecordPage() {
  const [page, setPage] = useState(1);
  const [generating, setGenerating] = useState(false);
  const [justifying, setJustifying] = useState<AttendanceRecord | null>(null);
  const [archiving, setArchiving] = useState<AttendanceRecord | null>(null);

  const { data, isLoading } = useAttendanceRecordList(page);
  const { data: enrollments } = useEnrollmentList(1);
  const update = useUpdateAttendance();
  const approve = useApproveJustification();
  const reject = useRejectJustification();
  const archive = useArchiveAttendance();

  const studentNames = useMemo(
    () => new Map((enrollments?.data ?? []).map((e) => [e.id, e.student?.full_name ?? e.id])),
    [enrollments]
  );

  const columns: DataTableColumn<AttendanceRecord>[] = [
    {
      key: 'student',
      header: 'Élève',
      render: (r) => (r.enrollment ? studentNames.get(r.enrollment.id) ?? '—' : '—'),
    },
    {
      key: 'status',
      header: 'Statut',
      render: (r) => <StatusBadge label={ATTENDANCE_STATUS_LABELS[r.status]} tone={STATUS_TONE[r.status]} />,
    },
    {
      key: 'justification',
      header: 'Justification',
      render: (r) =>
        r.justification_status ? (
          <span title={r.justification ?? ''}>{JUSTIFICATION_STATUS_LABELS[r.justification_status]}</span>
        ) : (
          '—'
        ),
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (r) => (
        <Can permission="attendance-record.manage">
          <div className="flex justify-end gap-1">
            {r.status !== 'PRESENT' && (
              <Button
                variant="ghost"
                size="sm"
                title="Marquer présent"
                onClick={() => update.mutate({ id: r.id, payload: { status: 'PRESENT' } })}
              >
                <RiCheckLine className="h-4 w-4" />
              </Button>
            )}
            {r.status !== 'ABSENT' && (
              <Button
                variant="ghost"
                size="sm"
                title="Marquer absent"
                onClick={() => update.mutate({ id: r.id, payload: { status: 'ABSENT' } })}
              >
                <RiCloseLine className="h-4 w-4" />
              </Button>
            )}
            {r.status === 'ABSENT' && !r.justification_status && (
              <Button variant="ghost" size="sm" title="Justifier" onClick={() => setJustifying(r)}>
                <RiFileTextLine className="h-4 w-4" />
              </Button>
            )}
            {r.justification_status === 'PENDING' && (
              <>
                <Button variant="ghost" size="sm" title="Approuver" onClick={() => approve.mutate(r.id)}>
                  <RiCheckDoubleLine className="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" title="Rejeter" onClick={() => reject.mutate(r.id)}>
                  <RiForbidLine className="h-4 w-4" />
                </Button>
              </>
            )}
            <Button variant="ghost" size="sm" title="Archiver" onClick={() => setArchiving(r)}>
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
        title="Présences"
        description="Appel par séance. Générez l'appel puis cochez les élèves présents."
        action={
          <Can permission="attendance-record.manage">
            <Button onClick={() => setGenerating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Générer l'appel
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(r) => r.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune présence"
        emptyDescription="Générez l'appel pour une séance en cours ou terminée."
      />

      <BulkGenerateDialog open={generating} onOpenChange={setGenerating} />

      <JustifyDialog record={justifying} onOpenChange={(open) => !open && setJustifying(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver cette présence ?"
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
