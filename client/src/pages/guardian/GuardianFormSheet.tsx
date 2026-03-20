import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreateGuardian, useUpdateGuardian } from '@/hooks/useGuardians';
import type { Guardian } from '@/types/guardian.types';

const schema = z.object({
  firstname: z.string().min(1, 'Le prénom est obligatoire.').max(255),
  lastname: z.string().min(1, 'Le nom est obligatoire.').max(255),
  phone: z.string().min(1, 'Le téléphone est obligatoire.').max(30),
  phone_secondary: z.string().max(30).nullable().optional(),
  email: z.union([z.string().email('Email invalide.'), z.literal('')]).nullable().optional(),
  profession: z.string().max(255).nullable().optional(),
  address: z.string().max(255).nullable().optional(),
  national_id: z.string().max(50).nullable().optional(),
});

const KNOWN_FIELDS = [
  'firstname',
  'lastname',
  'phone',
  'phone_secondary',
  'email',
  'profession',
  'address',
  'national_id',
] as const;

interface GuardianFormSheetProps {
  guardian: Guardian | null | 'create';
  onOpenChange: (open: boolean) => void;
}

/**
 * phone/email uniqueness is per school and does not exclude archived
 * guardians (server-side gap, see GuardianFormPayload). A 422 on either field
 * lands on that field via applyValidationErrors — the user sees why, even
 * though there is currently no path to resolve a conflict with an archived
 * record from this screen.
 */
export function GuardianFormSheet({ guardian, onOpenChange }: GuardianFormSheetProps) {
  const isEditing = guardian !== null && guardian !== 'create';
  const create = useCreateGuardian();
  const update = useUpdateGuardian(isEditing ? guardian.id : '');

  return (
    <FormSheet
      open={guardian !== null}
      onOpenChange={onOpenChange}
      title={isEditing ? `Modifier ${guardian.full_name}` : 'Nouveau parent'}
      schema={schema}
      defaultValues={{
        firstname: isEditing ? guardian.firstname : '',
        lastname: isEditing ? guardian.lastname : '',
        phone: isEditing ? guardian.phone : '',
        phone_secondary: isEditing ? guardian.phone_secondary ?? '' : '',
        email: isEditing ? guardian.email ?? '' : '',
        profession: isEditing ? guardian.profession ?? '' : '',
        address: isEditing ? guardian.address ?? '' : '',
        national_id: isEditing ? guardian.national_id ?? '' : '',
      }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => {
        const payload = { ...values, email: values.email || null };
        return (isEditing ? update.mutateAsync(payload) : create.mutateAsync(payload)).then(() => undefined);
      }}
    >
      {(form) => (
        <>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="firstname">Prénom</Label>
              <Input id="firstname" {...form.register('firstname')} />
              <FieldError error={form.formState.errors.firstname} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="lastname">Nom</Label>
              <Input id="lastname" {...form.register('lastname')} />
              <FieldError error={form.formState.errors.lastname} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="phone">Téléphone</Label>
              <Input id="phone" {...form.register('phone')} />
              <FieldError error={form.formState.errors.phone} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone_secondary">Téléphone secondaire</Label>
              <Input id="phone_secondary" {...form.register('phone_secondary')} />
              <FieldError error={form.formState.errors.phone_secondary} />
            </div>
          </div>
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" {...form.register('email')} />
            <FieldError error={form.formState.errors.email} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="profession">Profession</Label>
            <Input id="profession" {...form.register('profession')} />
            <FieldError error={form.formState.errors.profession} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="address">Adresse</Label>
            <Input id="address" {...form.register('address')} />
            <FieldError error={form.formState.errors.address} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="national_id">Pièce d'identité</Label>
            <Input id="national_id" {...form.register('national_id')} />
            <FieldError error={form.formState.errors.national_id} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
