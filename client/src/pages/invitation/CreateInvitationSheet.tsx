import { z } from 'zod';
import { FieldError, FormSheet } from '@/components/data/FormSheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCreateInvitation } from '@/hooks/useInvitations';
import { INVITATION_ROLE_LABELS, type InvitationRole } from '@/types/invitation.types';

const schema = z.object({
  email: z.string().email('Email invalide.').max(255),
  firstname: z.string().min(1, 'Le prénom est obligatoire.').max(255),
  lastname: z.string().min(1, 'Le nom est obligatoire.').max(255),
  role: z.enum([
    'OWNER',
    'ADMIN',
    'TEACHER',
    'SUPERVISOR',
    'ACCOUNTANT',
    'SECRETARY',
    'LIBRARIAN',
  ]),
});

const KNOWN_FIELDS = ['email', 'firstname', 'lastname', 'role'] as const;

interface CreateInvitationSheetProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * No uniqueness check on email server-side (StoreInvitationRequest — inline
 * validation in the controller, not a FormRequest — has no `unique` rule):
 * inviting the same address twice creates a second, independent invitation
 * rather than failing. Not a bug this form can fix.
 */
export function CreateInvitationSheet({ open, onOpenChange }: CreateInvitationSheetProps) {
  const createInvitation = useCreateInvitation();

  return (
    <FormSheet
      open={open}
      onOpenChange={onOpenChange}
      title="Inviter un membre du personnel"
      description="Un email contenant un lien d'activation sera envoyé à cette adresse."
      schema={schema}
      defaultValues={{ email: '', firstname: '', lastname: '', role: 'TEACHER' as InvitationRole }}
      knownFields={KNOWN_FIELDS}
      onSubmit={(values) => createInvitation.mutateAsync(values).then(() => undefined)}
    >
      {(form) => (
        <>
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" {...form.register('email')} />
            <FieldError error={form.formState.errors.email} />
          </div>
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
          <div className="space-y-2">
            <Label htmlFor="role">Rôle</Label>
            <select
              id="role"
              {...form.register('role')}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              {Object.entries(INVITATION_ROLE_LABELS).map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
            <FieldError error={form.formState.errors.role} />
          </div>
        </>
      )}
    </FormSheet>
  );
}
