import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePersonnelList } from '@/hooks/usePersonnel';
import { useCreateTeacher } from '@/hooks/useTeachers';
import type { Teacher } from '@/types/teacher.types';

const schema = z.object({
  personnel_id: z.string().uuid('Sélectionnez un membre du personnel.'),
  speciality: z.string().min(1, 'La spécialité est obligatoire.').max(255),
  diploma: z.string().max(255).nullable().optional(),
  license_number: z.string().max(255).nullable().optional(),
  years_experience: z.coerce.number().int().min(0).max(60).nullable().optional(),
});

const KNOWN_FIELDS = ['personnel_id', 'speciality', 'diploma', 'license_number', 'years_experience'] as const;

interface CreateTeacherSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  existingTeachers: Teacher[];
}

/**
 * TeacherService::create requires the target Personnel to already hold an
 * active TEACHER role in this school (checked server-side); the picker only
 * offers personnel who already do, so a submission fails only on a race, not
 * on a predictable user mistake.
 *
 * The picker is built from the first page of /personnel — that endpoint has
 * no search, so a school with more than one page of staff will not see every
 * eligible person here. A staff member missing from the list can still be
 * found by giving them the TEACHER role first (Personnel screen) then
 * reopening this sheet, but this is a real gap, not a cosmetic one.
 */
export function CreateTeacherSheet({ open, onOpenChange, existingTeachers }: CreateTeacherSheetProps) {
  const { data } = usePersonnelList(1);
  const createTeacher = useCreateTeacher();

  const teacherPersonnelIds = new Set(existingTeachers.map((t) => t.personnel_id));
  const eligible = (data?.data ?? []).filter(
    (p) => p.roles.includes('TEACHER') && !teacherPersonnelIds.has(p.id)
  );

  return (
    <FormSheet
      open={open}
      onOpenChange={onOpenChange}
      title="Nouveau profil enseignant"
      description="Le membre du personnel choisi doit déjà avoir le rôle Enseignant."
      schema={schema}
      defaultValues={{ personnel_id: '', speciality: '', diploma: '', license_number: '', years_experience: null }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => createTeacher.mutateAsync(values).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="personnel_id">Membre du personnel</Label>
            <select
              id="personnel_id"
              {...form.register('personnel_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {eligible.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.full_name} ({p.registration_number})
                </option>
              ))}
            </select>
            {eligible.length === 0 && (
              <p className="text-sm text-muted-foreground">
                Aucun personnel éligible sur cette page. Accordez d'abord le rôle Enseignant depuis
                l'écran Personnel.
              </p>
            )}
            <FieldError error={form.formState.errors.personnel_id} />
          </div>
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
