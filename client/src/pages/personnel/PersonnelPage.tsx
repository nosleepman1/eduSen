import { useState } from 'react';
import { RiShieldUserLine, RiPencilLine, RiArchiveLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { StatusBadge } from '@/components/data/StatusBadge';
import { ConfirmDialog } from '@/components/data/ConfirmDialog';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { usePersonnelList, useArchivePersonnel } from '@/hooks/usePersonnel';
import { EditPersonnelSheet } from './EditPersonnelSheet';
import { ManageRolesSheet } from './ManageRolesSheet';
import { STATUS_LABELS, type Personnel, type PersonnelStatus } from '@/types/personnel.types';

const STATUS_TONE: Record<PersonnelStatus, 'success' | 'warning' | 'danger'> = {
  ACTIVE: 'success',
  SUSPENDED: 'warning',
  TERMINATED: 'danger',
};

export default function PersonnelPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<Personnel | null>(null);
  const [managingRoles, setManagingRoles] = useState<Personnel | null>(null);
  const [archiving, setArchiving] = useState<Personnel | null>(null);

  const { data, isLoading } = usePersonnelList(page);
  const archive = useArchivePersonnel();

  const columns: DataTableColumn<Personnel>[] = [
    { key: 'full_name', header: 'Nom', render: (p) => p.full_name || '—' },
    { key: 'registration_number', header: 'Matricule', render: (p) => p.registration_number },
    { key: 'email', header: 'Email', render: (p) => p.email ?? '—' },
    { key: 'position', header: 'Poste', render: (p) => p.position ?? '—' },
    {
      key: 'roles',
      header: 'Rôles',
      render: (p) => (p.roles.length > 0 ? p.roles.join(', ') : '—'),
    },
    {
      key: 'status',
      header: 'Statut',
      render: (p) =>
        p.status ? <StatusBadge label={STATUS_LABELS[p.status]} tone={STATUS_TONE[p.status]} /> : '—',
    },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (p) => (
        <div className="flex justify-end gap-1">
          <Can permission="personnel.update">
            <Button variant="ghost" size="sm" onClick={() => setManagingRoles(p)} title="Gérer les rôles">
              <RiShieldUserLine className="h-4 w-4" />
            </Button>
            <Button variant="ghost" size="sm" onClick={() => setEditing(p)} title="Modifier">
              <RiPencilLine className="h-4 w-4" />
            </Button>
          </Can>
          <Can permission="personnel.delete">
            <Button variant="ghost" size="sm" onClick={() => setArchiving(p)} title="Archiver">
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
        title="Personnel"
        description="Membres du personnel de l'établissement. Un compte est créé par acceptation d'une invitation."
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(p) => p.id}
        isLoading={isLoading}
        meta={data?.meta ?? null}
        onPageChange={setPage}
        emptyTitle="Aucun membre du personnel"
        emptyDescription="Invitez un membre du personnel pour le voir apparaître ici."
      />

      <EditPersonnelSheet personnel={editing} onOpenChange={(open) => !open && setEditing(null)} />
      <ManageRolesSheet personnel={managingRoles} onOpenChange={(open) => !open && setManagingRoles(null)} />

      <ConfirmDialog
        open={archiving !== null}
        onOpenChange={(open) => !open && setArchiving(null)}
        title="Archiver ce membre du personnel ?"
        description={
          archiving
            ? `${archiving.full_name} sera suspendu et ses rôles désactivés. Cette action peut être annulée par un administrateur.`
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
