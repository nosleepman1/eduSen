import { useEffect, useState } from 'react';
import { RiDeleteBinLine, RiPencilLine } from 'react-icons/ri';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { StatusBadge } from '@/components/data/StatusBadge';
import { useSchoolCalendar, useAddHoliday, useUpdateHoliday, useRemoveHoliday } from '@/hooks/useSchoolCalendars';
import { HOLIDAY_TYPE_LABELS, type HolidayEvent, type HolidayType } from '@/types/schoolCalendar.types';

interface ManageHolidaysSheetProps {
  calendarId: string | null;
  calendarName: string;
  onOpenChange: (open: boolean) => void;
}

const EMPTY_FORM = { name: '', type: 'FIXED_HOLIDAY' as HolidayType, start_date: '', end_date: '', is_estimated: false };

/**
 * One sheet handles list + add + edit + delete for a calendar's holiday
 * events, rather than nesting a second Sheet inside this one — HolidayEvent
 * has no dedicated screen of its own, it only exists in the context of a
 * calendar.
 */
export function ManageHolidaysSheet({ calendarId, calendarName, onOpenChange }: ManageHolidaysSheetProps) {
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState(EMPTY_FORM);

  const { data: calendar } = useSchoolCalendar(calendarId);
  const addHoliday = useAddHoliday(calendarId ?? '');
  const updateHoliday = useUpdateHoliday(calendarId ?? '', editingId ?? '');
  const removeHoliday = useRemoveHoliday(calendarId ?? '');

  useEffect(() => {
    if (calendarId === null) {
      setEditingId(null);
      setForm(EMPTY_FORM);
    }
  }, [calendarId]);

  const startEditing = (holiday: HolidayEvent) => {
    setEditingId(holiday.id);
    setForm({
      name: holiday.name,
      type: holiday.type,
      start_date: holiday.start_date,
      end_date: holiday.end_date,
      is_estimated: holiday.is_estimated,
    });
  };

  const handleSubmit = async () => {
    if (editingId) {
      await updateHoliday.mutateAsync(form);
    } else {
      await addHoliday.mutateAsync(form);
    }
    setEditingId(null);
    setForm(EMPTY_FORM);
  };

  const pending = addHoliday.isPending || updateHoliday.isPending;

  return (
    <Sheet open={calendarId !== null} onOpenChange={onOpenChange}>
      <SheetContent className="overflow-y-auto">
        <SheetHeader>
          <SheetTitle>Jours fériés — {calendarName}</SheetTitle>
          <SheetDescription>
            Jours fériés et vacances de ce calendrier. Les dates ne peuvent pas se chevaucher.
          </SheetDescription>
        </SheetHeader>

        <div className="flex flex-col gap-4 px-4">
          <div>
            <p className="mb-2 text-sm font-medium text-foreground">Événements</p>
            {calendar?.holiday_events && calendar.holiday_events.length > 0 ? (
              <ul className="space-y-2">
                {calendar.holiday_events.map((h) => (
                  <li key={h.id} className="flex items-center justify-between rounded-md border p-2 text-sm">
                    <div>
                      <p className="font-medium">{h.name}</p>
                      <p className="text-muted-foreground">
                        {h.start_date} → {h.end_date}
                      </p>
                    </div>
                    <div className="flex items-center gap-2">
                      <StatusBadge label={HOLIDAY_TYPE_LABELS[h.type]} tone="info" />
                      {h.is_estimated && <StatusBadge label="Estimé" tone="warning" />}
                      <Button variant="ghost" size="sm" onClick={() => startEditing(h)} title="Modifier">
                        <RiPencilLine className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => removeHoliday.mutate(h.id)}
                        disabled={removeHoliday.isPending}
                        title="Supprimer"
                      >
                        <RiDeleteBinLine className="h-4 w-4" />
                      </Button>
                    </div>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-muted-foreground">Aucun événement pour le moment.</p>
            )}
          </div>

          <div className="space-y-3 border-t pt-4">
            <p className="text-sm font-medium text-foreground">
              {editingId ? "Modifier l'événement" : 'Ajouter un événement'}
            </p>
            <div className="space-y-2">
              <Label htmlFor="holiday-name">Nom</Label>
              <Input
                id="holiday-name"
                value={form.name}
                onChange={(e) => setForm({ ...form, name: e.target.value })}
                placeholder="Tabaski"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="holiday-type">Type</Label>
              <select
                id="holiday-type"
                value={form.type}
                onChange={(e) => setForm({ ...form, type: e.target.value as HolidayType })}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
              >
                {Object.entries(HOLIDAY_TYPE_LABELS).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="holiday-start">Début</Label>
                <Input
                  id="holiday-start"
                  type="date"
                  value={form.start_date}
                  onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="holiday-end">Fin</Label>
                <Input
                  id="holiday-end"
                  type="date"
                  value={form.end_date}
                  onChange={(e) => setForm({ ...form, end_date: e.target.value })}
                />
              </div>
            </div>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={form.is_estimated}
                onChange={(e) => setForm({ ...form, is_estimated: e.target.checked })}
                className="h-4 w-4 rounded border-input"
              />
              Date estimée (jour férié mobile non encore confirmé)
            </label>
            <div className="flex gap-2">
              {editingId && (
                <Button
                  variant="outline"
                  onClick={() => {
                    setEditingId(null);
                    setForm(EMPTY_FORM);
                  }}
                >
                  Annuler la modification
                </Button>
              )}
              <Button
                onClick={handleSubmit}
                disabled={pending || !form.name || !form.start_date || !form.end_date}
              >
                {pending ? 'Enregistrement...' : editingId ? 'Mettre à jour' : 'Ajouter'}
              </Button>
            </div>
          </div>
        </div>

        <SheetFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Fermer
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
