import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLevelList } from '@/hooks/useLevels';
import { useSubjectList } from '@/hooks/useSubjects';
import { useSpecializationList } from '@/hooks/useSpecializations';
import { useCreateCurriculum, useUpdateCurriculum } from '@/hooks/useCurriculums';
import type { Curriculum } from '@/types/curriculum.types';

const schema = z.object({
  level_id: z.string().uuid('Sélectionnez un niveau.'),
  specialization_id: z.string().nullable().optional(),
  subject_id: z.string().uuid('Sélectionnez une matière.'),
  coefficient: z.coerce.number().min(0.01, 'Le coefficient doit être supérieur à 0.'),
  weekly_hours: z.coerce.number().int().min(1, "Le volume horaire doit être d'au moins 1h."),
});

const KNOWN_FIELDS = ['level_id', 'specialization_id', 'subject_id', 'coefficient', 'weekly_hours'] as const;

interface CurriculumFormSheetProps {
  curriculum: Curriculum | null | 'create';
  onOpenChange: (open: boolean) => void;
}

/**
 * level_id is not editable — absent from UpdateCurriculumRequest. Pickers for
 * level/subject come from the first page of their respective endpoints, same
 * documented limitation as elsewhere (neither has search).
 */
export function CurriculumFormSheet({ curriculum, onOpenChange }: CurriculumFormSheetProps) {
  const isEditing = curriculum !== null && curriculum !== 'create';
  const { data: levels } = useLevelList(1);
  const { data: subjects } = useSubjectList();
  const { data: specializations } = useSpecializationList();
  const create = useCreateCurriculum();
  const update = useUpdateCurriculum(isEditing ? curriculum.id : '');

  return (
    <FormSheet
      open={curriculum !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? 'Modifier la ligne de programme' : 'Nouvelle ligne de programme'}
      schema={schema}
      defaultValues={{
        level_id: isEditing ? curriculum.level_id : '',
        specialization_id: isEditing ? curriculum.specialization_id ?? '' : '',
        subject_id: isEditing ? curriculum.subject_id : '',
        coefficient: isEditing ? curriculum.coefficient : 1,
        weekly_hours: isEditing ? curriculum.weekly_hours : 1,
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => {
        const payload = { ...values, specialization_id: values.specialization_id || null };
        return (isEditing
          ? update.mutateAsync({
              specialization_id: payload.specialization_id,
              subject_id: payload.subject_id,
              coefficient: payload.coefficient,
              weekly_hours: payload.weekly_hours,
            })
          : create.mutateAsync(payload)
        ).then(() => undefined);
      }}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="level_id">Niveau</Label>
            <select
              id="level_id"
              {...form.register('level_id')}
              disabled={isEditing}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:opacity-60"
            >
              <option value="">Sélectionner...</option>
              {(levels?.data ?? []).map((l) => (
                <option key={l.id} value={l.id}>
                  {l.name}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.level_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="specialization_id">Spécialisation</Label>
            <select
              id="specialization_id"
              {...form.register('specialization_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Aucune</option>
              {(specializations?.data ?? []).map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-2">
            <Label htmlFor="subject_id">Matière</Label>
            <select
              id="subject_id"
              {...form.register('subject_id')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(subjects?.data ?? []).map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.subject_id} />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="coefficient">Coefficient</Label>
              <Input id="coefficient" type="number" min={0.01} step={0.01} {...form.register('coefficient')} />
              <FieldError error={form.formState.errors.coefficient} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="weekly_hours">Heures / semaine</Label>
              <Input id="weekly_hours" type="number" min={1} {...form.register('weekly_hours')} />
              <FieldError error={form.formState.errors.weekly_hours} />
            </div>
          </div>
        </>
      )}
    </FormSheet>
  );
}
