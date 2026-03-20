import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreateSpecialization, useUpdateSpecialization } from '@/hooks/useSpecializations';
import type { Specialization } from '@/types/specialization.types';

const schema = z.object({
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
  code: z.string().min(1, 'Le code est obligatoire.').max(50),
  description: z.string().nullable().optional(),
});

const KNOWN_FIELDS = ['name', 'code', 'description'] as const;

interface SpecializationFormSheetProps {
  specialization: Specialization | null | 'create';
  onOpenChange: (open: boolean) => void;
}

export function SpecializationFormSheet({ specialization, onOpenChange }: SpecializationFormSheetProps) {
  const isEditing = specialization !== null && specialization !== 'create';
  const create = useCreateSpecialization();
  const update = useUpdateSpecialization(isEditing ? specialization.id : '');

  return (
    <FormSheet
      open={specialization !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${specialization.name}` : 'Nouvelle spécialisation'}
      schema={schema}
      defaultValues={{
        name: isEditing ? specialization.name : '',
        code: isEditing ? specialization.code : '',
        description: isEditing ? specialization.description ?? '' : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => (isEditing ? update.mutateAsync(values) : create.mutateAsync(values)).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="name">Nom</Label>
            <Input id="name" placeholder="Scientifique" {...form.register('name')} />
            <FieldError error={form.formState.errors.name} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="code">Code</Label>
            <Input id="code" placeholder="SCI" {...form.register('code')} />
            <FieldError error={form.formState.errors.code} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <textarea
              id="description"
              {...form.register('description')}
              className="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
            />
            <FieldError error={form.formState.errors.description} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
