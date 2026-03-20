import { useEffect, useState } from 'react';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { useUpdatePersonnelRoles } from '@/hooks/usePersonnel';
import { ASSIGNABLE_ROLES, ROLE_LABELS, type AssignableRole, type Personnel } from '@/types/personnel.types';

interface ManageRolesSheetProps {
  personnel: Personnel | null;
  onOpenChange: (open: boolean) => void;
}

/**
 * PATCH /personnel/{id}/roles replaces the full role set (sync), not a diff —
 * every checkbox state is sent on submit, not just the ones the user touched.
 */
export function ManageRolesSheet({ personnel, onOpenChange }: ManageRolesSheetProps) {
  const [selected, setSelected] = useState<Set<AssignableRole>>(new Set());
  const updateRoles = useUpdatePersonnelRoles(personnel?.id ?? '');

  useEffect(() => {
    if (personnel) {
      setSelected(new Set(personnel.roles.filter(isAssignable)));
    }
  }, [personnel]);

  const toggle = (role: AssignableRole) => {
    setSelected((prev) => {
      const next = new Set(prev);
      if (next.has(role)) {
        next.delete(role);
      } else {
        next.add(role);
      }
      return next;
    });
  };

  const handleSubmit = async () => {
    await updateRoles.mutateAsync({ roles: Array.from(selected) });
    onOpenChange(false);
  };

  return (
    <Sheet open={personnel !== null} onOpenChange={onOpenChange}>
      <SheetContent>
        <SheetHeader>
          <SheetTitle>{personnel ? `Rôles de ${personnel.full_name}` : 'Rôles'}</SheetTitle>
          <SheetDescription>
            Sélectionnez les rôles à accorder dans cet établissement. Le rôle Propriétaire ne se
            gère pas ici.
          </SheetDescription>
        </SheetHeader>

        <div className="flex flex-col gap-3 px-4">
          {ASSIGNABLE_ROLES.map((role) => (
            <label key={role} className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={selected.has(role)}
                onChange={() => toggle(role)}
                className="h-4 w-4 rounded border-input"
              />
              {ROLE_LABELS[role]}
            </label>
          ))}
        </div>

        <SheetFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={updateRoles.isPending}>
            Annuler
          </Button>
          <Button onClick={handleSubmit} disabled={updateRoles.isPending}>
            {updateRoles.isPending ? 'Enregistrement...' : 'Enregistrer'}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}

function isAssignable(role: string): role is AssignableRole {
  return (ASSIGNABLE_ROLES as string[]).includes(role);
}
