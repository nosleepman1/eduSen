import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAcademicYearList } from '@/hooks/useAcademicYears';
import { useCreateAcademicPeriod, useUpdateAcademicPeriod } from '@/hooks/useAcademicPeriods';
import type { AcademicPeriod } from '@/types/academicPeriod.types';

const schema = z.object({
  academic_year_id: z.string().uuid("Sélectionnez une année scolaire."),
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
  start_date: z.string().min(1, 'La date de début est obligatoire.'),
  end_date: z.string().min(1, 'La date de fin est obligatoire.'),
});

const KNOWN_FIELDS = ['academic_year_id', 'name', 'start_date', 'end_date'] as const;

interface AcademicPeriodFormSheetProps {
  period: AcademicPeriod | null | 'create';
  /** Preselected when creating from an already-filtered list. */
  defaultAcademicYearId?: string;
  onOpenChange: (open: boolean) => void;
}

/**
 * The year picker is built from the first page of /academic/years — that
 * endpoint has no search, so a school with many years on file may not see
 * every one here. Same documented limitation as the personnel/guardian
 * pickers on other domains.
 */
export function AcademicPeriodFormSheet({ period, defaultAcademicYearId, onOpenChange }: AcademicPeriodFormSheetProps) {
  const isEditing = period !== null && period !== 'create';
  const { data: years } = useAcademicYearList(1);
  const create = useCreateAcademicPeriod();
  const update = useUpdateAcademicPeriod(isEditing ? period.id : '');

  return (
    <FormSheet
      open={period !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${period.name}` : 'Nouvelle période académique'}
      schema={schema}
      defaultValues={{
        academic_year_id: isEditing ? period.academic_year_id : defaultAcademicYearId ?? '',
        name: isEditing ? period.name : '',
        start_date: isEditing ? period.start_date : '',
        end_date: isEditing ? period.end_date : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) =>
        (isEditing
          ? update.mutateAsync({ name: values.name, start_date: values.start_date, end_date: values.end_date })
          : create.mutateAsync(values)
        ).then(() => undefined)
      }
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="academic_year_id">Année scolaire</Label>
            <select
              id="academic_year_id"
              {...form.register('academic_year_id')}
              disabled={isEditing}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:opacity-60"
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
            <Input id="name" placeholder="Trimestre 1" {...form.register('name')} />
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
