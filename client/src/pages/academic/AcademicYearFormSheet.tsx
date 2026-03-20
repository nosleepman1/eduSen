import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreateAcademicYear, useUpdateAcademicYear } from '@/hooks/useAcademicYears';
import type { AcademicYear } from '@/types/academicYear.types';

const schema = z.object({
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
  start_date: z.string().min(1, 'La date de début est obligatoire.'),
  end_date: z.string().min(1, 'La date de fin est obligatoire.'),
});

const KNOWN_FIELDS = ['name', 'start_date', 'end_date'] as const;

interface AcademicYearFormSheetProps {
  year: AcademicYear | null | 'create';
  onOpenChange: (open: boolean) => void;
}

/**
 * Only start_date/end_date/name — status is never editable here (activate/
 * close/reopen are separate transition actions on the list screen). Editing
 * is blocked server-side once the year is CLOSED (AcademicYearService::update
 * throws AcademicYearAlreadyClosedException); that 422 has no field key, so
 * it surfaces as a toast rather than a field message — expected, not a bug.
 */
export function AcademicYearFormSheet({ year, onOpenChange }: AcademicYearFormSheetProps) {
  const isEditing = year !== null && year !== 'create';
  const create = useCreateAcademicYear();
  const update = useUpdateAcademicYear(isEditing ? year.id : '');

  return (
    <FormSheet
      open={year !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${year.name}` : 'Nouvelle année scolaire'}
      schema={schema}
      defaultValues={{
        name: isEditing ? year.name : '',
        start_date: isEditing ? year.start_date : '',
        end_date: isEditing ? year.end_date : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => (isEditing ? update.mutateAsync(values) : create.mutateAsync(values)).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="name">Nom</Label>
            <Input id="name" placeholder="2026 - 2027" {...form.register('name')} />
            <FieldError error={form.formState.errors.name} />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="start_date">Date de début</Label>
              <Input id="start_date" type="date" {...form.register('start_date')} />
              <FieldError error={form.formState.errors.start_date} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="end_date">Date de fin</Label>
              <Input id="end_date" type="date" {...form.register('end_date')} />
              <FieldError error={form.formState.errors.end_date} />
            </div>
          </div>
        </>
      )}
    </FormSheet>
  );
}
