import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClassroomList } from '@/hooks/useClassrooms';
import { useCreateTimetable, useUpdateTimetable } from '@/hooks/useTimetables';
import type { Timetable } from '@/types/timetable.types';

const schema = z.object({
  classroom_id: z.string().uuid('Sélectionnez une classe.'),
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
  description: z.string().nullable().optional(),
});

const KNOWN_FIELDS = ['classroom_id', 'name', 'description'] as const;

interface TimetableFormSheetProps {
  timetable: Timetable | null | 'create';
  onOpenChange: (open: boolean) => void;
}

/** classroom_id is not editable: absent from UpdateTimetableRequest. */
export function TimetableFormSheet({ timetable, onOpenChange }: TimetableFormSheetProps) {
  const isEditing = timetable !== null && timetable !== 'create';
  const { data: classrooms } = useClassroomList(1);
  const create = useCreateTimetable();
  const update = useUpdateTimetable(isEditing ? timetable.id : '');

  return (
    <FormSheet
      open={timetable !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${timetable.name}` : 'Nouvel emploi du temps'}
      schema={schema}
      defaultValues={{
        classroom_id: isEditing ? timetable.classroom_id : '',
        name: isEditing ? timetable.name : '',
        description: isEditing ? timetable.description ?? '' : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) =>
        (isEditing
          ? update.mutateAsync({ name: values.name, description: values.description })
          : create.mutateAsync(values)
        ).then(() => undefined)
      }
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="classroom_id">Classe</Label>
            <select
              id="classroom_id"
              {...form.register('classroom_id')}
              disabled={isEditing}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:opacity-60"
            >
              <option value="">Sélectionner...</option>
              {(classrooms?.data ?? []).map((c) => (
                <option key={c.id} value={c.id}>
                  {c.full_name}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.classroom_id} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="name">Nom</Label>
            <Input id="name" placeholder="Emploi du temps général" {...form.register('name')} />
            <FieldError error={form.formState.errors.name} />
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
