import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAcademicYearList } from '@/hooks/useAcademicYears';
import { useSpecializationList } from '@/hooks/useSpecializations';
import { useCreateLevel, useUpdateLevel } from '@/hooks/useLevels';
import type { Level } from '@/types/level.types';

const schema = z.object({
  academic_year_id: z.string().uuid("Sélectionnez une année scolaire."),
  specialization_id: z.string().nullable().optional(),
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
  code: z.string().min(1, 'Le code est obligatoire.').max(50),
  description: z.string().nullable().optional(),
});

const KNOWN_FIELDS = ['academic_year_id', 'specialization_id', 'name', 'code', 'description'] as const;

interface LevelFormSheetProps {
  level: Level | null | 'create';
  defaultAcademicYearId?: string;
  onOpenChange: (open: boolean) => void;
}

/**
 * display_order has no field here: LevelService::create() auto-assigns the
 * next position within the year when omitted, and reordering is a dedicated
 * action on the list screen (drag would conflict with a filtered/paginated
 * view anyway). name/code are unique per academic_year_id, not per school —
 * two different years can reuse "6ème"/"L1".
 */
export function LevelFormSheet({ level, defaultAcademicYearId, onOpenChange }: LevelFormSheetProps) {
  const isEditing = level !== null && level !== 'create';
  const { data: years } = useAcademicYearList(1);
  const { data: specializations } = useSpecializationList();
  const create = useCreateLevel();
  const update = useUpdateLevel(isEditing ? level.id : '');

  return (
    <FormSheet
      open={level !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${level.name}` : 'Nouveau niveau'}
      schema={schema}
      defaultValues={{
        academic_year_id: isEditing ? level.academic_year_id : defaultAcademicYearId ?? '',
        specialization_id: isEditing ? level.specialization_id ?? '' : '',
        name: isEditing ? level.name : '',
        code: isEditing ? level.code : '',
        description: isEditing ? level.description ?? '' : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => {
        const payload = { ...values, specialization_id: values.specialization_id || null };
        return (isEditing
          ? update.mutateAsync({ specialization_id: payload.specialization_id, name: payload.name, code: payload.code, description: payload.description })
          : create.mutateAsync(payload)
        ).then(() => undefined);
      }}
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
            <Label htmlFor="name">Nom</Label>
            <Input id="name" placeholder="Terminale" {...form.register('name')} />
            <FieldError error={form.formState.errors.name} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="code">Code</Label>
            <Input id="code" placeholder="TL" {...form.register('code')} />
            <FieldError error={form.formState.errors.code} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <textarea
              id="description"
              {...form.register('description')}
              className="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
            />
          </div>
        </>
      )}
    </FormSheet>
  );
}
