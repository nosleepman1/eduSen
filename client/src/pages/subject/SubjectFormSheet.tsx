import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreateSubject, useUpdateSubject } from '@/hooks/useSubjects';
import type { Subject } from '@/types/subject.types';

const schema = z.object({
  name: z.string().min(1, 'Le nom est obligatoire.').max(255),
  code: z.string().min(1, 'Le code est obligatoire.').max(50),
  description: z.string().nullable().optional(),
});

const KNOWN_FIELDS = ['name', 'code', 'description'] as const;

interface SubjectFormSheetProps {
  subject: Subject | null | 'create';
  onOpenChange: (open: boolean) => void;
}

export function SubjectFormSheet({ subject, onOpenChange }: SubjectFormSheetProps) {
  const isEditing = subject !== null && subject !== 'create';
  const create = useCreateSubject();
  const update = useUpdateSubject(isEditing ? subject.id : '');

  return (
    <FormSheet
      open={subject !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${subject.name}` : 'Nouvelle matière'}
      schema={schema}
      defaultValues={{
        name: isEditing ? subject.name : '',
        code: isEditing ? subject.code : '',
        description: isEditing ? subject.description ?? '' : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => (isEditing ? update.mutateAsync(values) : create.mutateAsync(values)).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="name">Nom</Label>
            <Input id="name" placeholder="Mathématiques" {...form.register('name')} />
            <FieldError error={form.formState.errors.name} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="code">Code</Label>
            <Input id="code" placeholder="MATH" {...form.register('code')} />
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
