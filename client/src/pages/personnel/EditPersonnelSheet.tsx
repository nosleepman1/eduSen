import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useUpdatePersonnel } from '@/hooks/usePersonnel';
import type { Personnel } from '@/types/personnel.types';

const schema = z.object({
  department: z.string().max(255).nullable().optional(),
  position: z.string().max(255).nullable().optional(),
  hire_date: z.string().nullable().optional(),
});

const KNOWN_FIELDS = ['department', 'position', 'hire_date'] as const;

interface EditPersonnelSheetProps {
  personnel: Personnel | null;
  onOpenChange: (open: boolean) => void;
}

/**
 * Only department/position/hire_date are editable here: UpdatePersonnelRequest
 * (server) does not accept firstname/lastname/email/status — those live on the
 * linked user record and status changes only through archive/restore.
 */
export function EditPersonnelSheet({ personnel, onOpenChange }: EditPersonnelSheetProps) {
  const update = useUpdatePersonnel(personnel?.id ?? '');

  return (
    <FormSheet
      open={personnel !== null}
      onOpenChange={onOpenChange}
      title={personnel ? `Modifier ${personnel.full_name}` : 'Modifier'}
      schema={schema}
      defaultValues={{
        department: personnel?.department ?? '',
        position: personnel?.position ?? '',
        hire_date: personnel?.hire_date ?? '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => update.mutateAsync(values).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="department">Département</Label>
            <Input id="department" {...form.register('department')} />
            <FieldError error={form.formState.errors.department} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="position">Poste</Label>
            <Input id="position" {...form.register('position')} />
            <FieldError error={form.formState.errors.position} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="hire_date">Date d'embauche</Label>
            <Input id="hire_date" type="date" {...form.register('hire_date')} />
            <FieldError error={form.formState.errors.hire_date} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
