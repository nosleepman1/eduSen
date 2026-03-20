import { useState } from 'react';
import { RiAddLine, RiCalendarEventLine } from 'react-icons/ri';
import { PageHeader } from '@/components/data/PageHeader';
import { DataTable, type DataTableColumn } from '@/components/data/DataTable';
import { Can } from '@/components/auth/Can';
import { Button } from '@/components/ui/button';
import { useSchoolCalendarList } from '@/hooks/useSchoolCalendars';
import { CreateCalendarSheet } from './CreateCalendarSheet';
import { ManageHolidaysSheet } from './ManageHolidaysSheet';
import type { SchoolCalendar } from '@/types/schoolCalendar.types';

export default function SchoolCalendarPage() {
  const [creating, setCreating] = useState(false);
  const [managing, setManaging] = useState<SchoolCalendar | null>(null);

  const { data, isLoading } = useSchoolCalendarList();

  const columns: DataTableColumn<SchoolCalendar>[] = [
    { key: 'name', header: 'Calendrier', render: (c) => c.name },
    {
      key: 'actions',
      header: '',
      className: 'text-right',
      render: (c) => (
        <Can permission="school-calendar.manage">
          <Button variant="ghost" size="sm" onClick={() => setManaging(c)} title="Gérer les jours fériés">
            <RiCalendarEventLine className="mr-1 h-4 w-4" />
            Jours fériés
          </Button>
        </Can>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Calendriers scolaires"
        description="Un calendrier par année scolaire, avec ses jours fériés et vacances."
        action={
          <Can permission="school-calendar.manage">
            <Button onClick={() => setCreating(true)}>
              <RiAddLine className="mr-1 h-4 w-4" />
              Nouveau calendrier
            </Button>
          </Can>
        }
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(c) => c.id}
        isLoading={isLoading}
        emptyTitle="Aucun calendrier scolaire"
        emptyDescription="Créez un calendrier pour une année scolaire afin d'y ajouter des jours fériés."
      />

      <CreateCalendarSheet open={creating} onOpenChange={setCreating} />

      <ManageHolidaysSheet
        calendarId={managing?.id ?? null}
        calendarName={managing?.name ?? ''}
        onOpenChange={(open) => !open && setManaging(null)}
      />
    </div>
  );
}
