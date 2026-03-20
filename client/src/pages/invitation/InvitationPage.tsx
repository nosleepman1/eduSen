import { useState } from 'react';
import { format } from 'date-fns';
import { RiAddLine, RiMailSendLine, RiCloseCircleLine, RiDeleteBinLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge, type Tone } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/hooks/useAuth';
import {
  useInvitationList,
  useResendInvitation,
  useCancelInvitation,
  useDeleteInvitation,
} from '@/hooks/useInvitations';
import { CreateInvitationSheet } from './CreateInvitationSheet';
import { INVITATION_ROLE_LABELS, INVITATION_STATUS_LABELS, type Invitation } from '@/types/invitation.types';

const STATUS_TONE: Record<string, Tone> = {
  PENDING: 'warning',
  ACCEPTED: 'success',
  EXPIRED: 'neutral',
  CANCELLED: 'danger',
};

/**
 * Effective status for display: `status` alone can lie (Invitation::isExpired()
 * also checks expires_at, which the PENDING row on the server doesn't update
 * until something touches it). is_expired is computed server-side — trust it.
 */
function displayStatus(invitation: Invitation): { label: string; tone: Tone } {
  if (invitation.status === 'PENDING' && invitation.is_expired) {
    return { label: 'Expirée', tone: 'neutral' };
  }
  return { label: INVITATION_STATUS_LABELS[invitation.status], tone: STATUS_TONE[invitation.status] };
}

export default function InvitationPage() {
  const [page, setPage] = useState(1);
  const [creating, setCreating] = useState(false);
  const [cancelling, setCancelling] = useState<Invitation | null>(null);
  const [deleting, setDeleting] = useState<Invitation | null>(null);

  const { role } = useAuth();
  // This domain is gated by role:OWNER,ADMIN in routes/api/v1/school.php, not
  // by a permission — the invitation.* permissions exist in the seeder but
  // guard no route, so <Can permission="..."> would be the wrong tool here.
  const canManage = role === 'OWNER' || role === 'ADMIN';

  const { data, isLoading } = useInvitationList(page);
  const resend = useResendInvitation();
  const cancel = useCancelInvitation();
  const destroy = useDeleteInvitation();

  const columns: DataTableColumn<Invitation>[] = [
    { key: 'name', header: 'Invité', render: (i) => `${i.firstname} ${i.lastname}` },
    { key: 'email', header: 'Email', render: (i) => i.email },
    { key: 'role', header: 'Rôle', render: (i) => INVITATION_ROLE_LABELS[i.role] },
    {
      key: 'status',
      header: 'Statut',
      render: (i) => {
        const { label, tone } = displayStatus(i);
        return <StatusBadge label={label} tone={tone} />;
      },
    },
    {
      key: 'expires_at',
      header: 'Expire le',
      render: (i) => (i.expires_at ? format(new Date(i.expires_at), 'dd/MM/yyyy') : '—'),
    },
    ...(canManage
      ? [
          {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (i: Invitation) => (
              <div className="flex justify-end gap-1">
                {i.status === 'PENDING' && !i.is_expired && (
                  <>
                    <Button variant="ghost" size="sm" onClick={() => resend.mutate(i.id)} title="Renvoyer">
                      <RiMailSendLine className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => setCancelling(i)} title="Annuler">
                      <RiCloseCircleLine className="h-4 w-4" />
                    </Button>
                  </>
                )}
                <Button variant="ghost" size="sm" onClick={() => setDeleting(i)} title="Supprimer">
                  <RiDeleteBinLine className="h-4 w-4" />
                </Button>
              </div>
            ),
          },
        ]
      : []),
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Invitations"
        description="Invitez un nouveau membre du personnel par email. Il choisit son mot de passe en acceptant."
        action={
          canManage ? (
            <Button onClick={() => setCreating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Inviter
            </Button>
          ) : undefined
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(i) => i.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucune invitation"
        emptyDescription="Invitez un membre du personnel pour le voir apparaître ici."
      />

      <CreateInvitationSheet open={creating} onOpenChange={setCreating} />

      <ConfirmDialog
        open={cancelling !== null}
        onOpenChange={(open) => !open && setCancelling(null)}
        title="Annuler cette invitation ?"
        description={cancelling ? `L'invitation envoyée à ${cancelling.email} ne pourra plus être acceptée.` : ''}
        confirmLabel="Annuler l'invitation"
        destructive
        isConfirming={cancel.isPending}
        onConfirm={async () => {
          if (cancelling) {
            await cancel.mutateAsync(cancelling.id);
            setCancelling(null);
          }
        }}
      />

      <ConfirmDialog
        open={deleting !== null}
        onOpenChange={(open) => !open && setDeleting(null)}
        title="Supprimer cette invitation ?"
        description={
          deleting
            ? `L'invitation envoyée à ${deleting.email} sera définitivement supprimée — Invitation n'a pas de corbeille, cette action est irréversible.`
            : ''
        }
        confirmLabel="Supprimer"
        destructive
        isConfirming={destroy.isPending}
        onConfirm={async () => {
          if (deleting) {
            await destroy.mutateAsync(deleting.id);
            setDeleting(null);
          }
        }}
      />
    </div>
  );
}
