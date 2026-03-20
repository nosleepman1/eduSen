import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAcademicYearList } from '@/hooks/useAcademicYears';
import { useCreateSchoolCalendar } from '@/hooks/useSchoolCalendars';

const schema = z.object({
  academic_year_id: z.string().uuid("Sélectionnez une année scolaire."),
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
});

const KNOWN_FIELDS = ['academic_year_id', 'name'] as const;

interface CreateCalendarSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * There is no update or delete route for a calendar itself — only creation
 * and holiday management within it (verified in routes/api/v1/academic.php).
 * Once created, a calendar's year and name are fixed from this screen's
 * point of view.
 */
export function CreateCalendarSheet({ open, onOpenChange }: CreateCalendarSheetProps) {
  const { data: years } = useAcademicYearList(1);
  const create = useCreateSchoolCalendar();

  return (
    <FormSheet
      open={open}
      onOpenChange={onOpenChange}
      title="Nouveau calendrier scolaire"
      schema={schema}
      defaultValues={{ academic_year_id: '', name: '' }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => create.mutateAsync(values).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="academic_year_id">Année scolaire</Label>
            <select
              id="academic_year_id"
              {...form.register('academic_year_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(years?.data ?? []).map((y) => (
                <option key={y.id} value={y.id}>
                  {y.name}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.academic_year_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="name">Nom</Label>
            <Input id="name" placeholder="Calendrier 2026 - 2027" {...form.register('name')} />
            <FieldError error={form.formState.errors.name} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
