import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useUpdateTeacher } from '@/hooks/useTeachers';
import type { Teacher } from '@/types/teacher.types';

const schema = z.object({
  speciality: z.string().min(1, 'La spécialité est obligatoire.').max(255),
  diploma: z.string().max(255).nullable().optional(),
  license_number: z.string().max(255).nullable().optional(),
  years_experience: z.coerce.number().int().min(0).max(60).nullable().optional(),
});

const KNOWN_FIELDS = ['speciality', 'diploma', 'license_number', 'years_experience'] as const;

interface EditTeacherSheetProps {
  teacher: Teacher | null;
  onOpenChange: (open: boolean) => void;
}

/** personnel_id is not editable — absent from UpdateTeacherRequest on the server. */
export function EditTeacherSheet({ teacher, onOpenChange }: EditTeacherSheetProps) {
  const update = useUpdateTeacher(teacher?.id ?? '');

  return (
    <FormSheet
      open={teacher !== null}
      onOpenChange={onOpenChange}
      title={teacher ? `Modifier ${teacher.full_name}` : 'Modifier'}
      schema={schema}
      defaultValues={{
        speciality: teacher?.speciality ?? '',
        diploma: teacher?.diploma ?? '',
        license_number: teacher?.license_number ?? '',
        years_experience: teacher?.years_experience ?? null,
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => update.mutateAsync(values).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="speciality">Spécialité</Label>
            <Input id="speciality" {...form.register('speciality')} />
            <FieldError error={form.formState.errors.speciality} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="diploma">Diplôme</Label>
            <Input id="diploma" {...form.register('diploma')} />
            <FieldError error={form.formState.errors.diploma} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="license_number">Numéro de licence</Label>
            <Input id="license_number" {...form.register('license_number')} />
            <FieldError error={form.formState.errors.license_number} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="years_experience">Années d'expérience</Label>
            <Input id="years_experience" type="number" min={0} max={60} {...form.register('years_experience')} />
            <FieldError error={form.formState.errors.years_experience} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
