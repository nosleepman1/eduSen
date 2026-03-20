import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLevelList } from '@/hooks/useLevels';
import { useSpecializationList } from '@/hooks/useSpecializations';
import { useCreateClassroom, useUpdateClassroom } from '@/hooks/useClassrooms';
import type { Classroom } from '@/types/classroom.types';

const schema = z.object({
  level_id: z.string().uuid('Sélectionnez un niveau.'),
  specialization_id: z.string().nullable().optional(),
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
  code: z.string().max(100).nullable().optional(),
  capacity: z.coerce.number().int().min(1, 'La capacité doit être au moins 1.'),
  main_room: z.string().max(255).nullable().optional(),
  description: z.string().nullable().optional(),
  end_date_override: z.string().nullable().optional(),
});

const KNOWN_FIELDS = [
  'level_id',
  'specialization_id',
  'name',
  'code',
  'capacity',
  'main_room',
  'description',
  'end_date_override',
] as const;

interface ClassroomFormSheetProps {
  classroom: Classroom | null | 'create';
  onOpenChange: (open: boolean) => void;
}

/**
 * The level picker is built from the first page of /academic/levels — that
 * endpoint has no filter (documented on the Level screen too). code is left
 * blank on create: the server generates one from level.code + specialization
 * + name when omitted, so the placeholder says so instead of a fake example.
 * level_id is not editable: absent from UpdateClassroomRequest.
 */
export function ClassroomFormSheet({ classroom, onOpenChange }: ClassroomFormSheetProps) {
  const isEditing = classroom !== null && classroom !== 'create';
  const { data: levels } = useLevelList(1);
  const { data: specializations } = useSpecializationList();
  const create = useCreateClassroom();
  const update = useUpdateClassroom(isEditing ? classroom.id : '');

  return (
    <FormSheet
      open={classroom !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${classroom.full_name}` : 'Nouvelle classe'}
      schema={schema}
      defaultValues={{
        level_id: isEditing ? classroom.level_id : '',
        specialization_id: isEditing ? classroom.specialization_id ?? '' : '',
        name: isEditing ? classroom.name : '',
        code: isEditing ? classroom.code : '',
        capacity: isEditing ? classroom.capacity : 30,
        main_room: isEditing ? classroom.main_room ?? '' : '',
        description: isEditing ? classroom.description ?? '' : '',
        end_date_override: isEditing ? classroom.end_date_override ?? '' : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => {
        const payload = { ...values, specialization_id: values.specialization_id || null, code: values.code || null };
        return (isEditing
          ? update.mutateAsync({
              specialization_id: payload.specialization_id,
              name: payload.name,
              code: payload.code,
              capacity: payload.capacity,
              main_room: payload.main_room,
              description: payload.description,
              end_date_override: payload.end_date_override || null,
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
            <Label htmlFor="name">Nom</Label>
            <Input id="name" placeholder="A" {...form.register('name')} />
            <FieldError error={form.formState.errors.name} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="code">Code</Label>
            <Input id="code" placeholder="Généré automatiquement si laissé vide" {...form.register('code')} />
            <FieldError error={form.formState.errors.code} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="capacity">Capacité</Label>
            <Input id="capacity" type="number" min={1} {...form.register('capacity')} />
            <FieldError error={form.formState.errors.capacity} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="main_room">Salle principale</Label>
            <Input id="main_room" {...form.register('main_room')} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="end_date_override">Date de fin (si différente de l'année)</Label>
            <Input id="end_date_override" type="date" {...form.register('end_date_override')} />
            <FieldError error={form.formState.errors.end_date_override} />
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
