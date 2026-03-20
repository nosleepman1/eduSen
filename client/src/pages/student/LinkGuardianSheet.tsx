import { useState } from 'react';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { StatusBadge } from '@/components/data/StatusBadge';
import { useGuardianPicker, useLinkGuardian, useStudent } from '@/hooks/useStudents';
import { RELATIONSHIP_LABELS, type GuardianRelationship } from '@/types/student.types';

interface LinkGuardianSheetProps {
  studentId: string | null;
  studentName: string;
  onOpenChange: (open: boolean) => void;
}

/**
 * There is no unlink endpoint (verified against routes/api/v1/school.php):
 * this panel can only add a guardian or update the relationship/primary flag
 * of one already linked, by resubmitting the same guardian_id.
 *
 * The guardian picker is limited to the first page of /guardians — that
 * endpoint has no search either. A school with many parents on file may not
 * find the right one here; this is the same documented gap as the teacher
 * picker, not a bug specific to this screen.
 */
export function LinkGuardianSheet({ studentId, studentName, onOpenChange }: LinkGuardianSheetProps) {
  const [guardianId, setGuardianId] = useState('');
  const [relationship, setRelationship] = useState<GuardianRelationship>('FATHER');
  const [isPrimary, setIsPrimary] = useState(false);

  const { data: student } = useStudent(studentId);
  const { data: guardians } = useGuardianPicker();
  const linkGuardian = useLinkGuardian(studentId ?? '');

  const handleSubmit = async () => {
    if (!guardianId) return;
    await linkGuardian.mutateAsync({ guardian_id: guardianId, relationship, is_primary: isPrimary });
    setGuardianId('');
    setIsPrimary(false);
  };

  return (
    <Sheet open={studentId !== null} onOpenChange={onOpenChange}>
      <SheetContent className="overflow-y-auto">
        <SheetHeader>
          <SheetTitle>Tuteurs de {studentName}</SheetTitle>
          <SheetDescription>
            Associer un tuteur existant. Il n'est pas possible de retirer un tuteur déjà associé,
            seulement de mettre à jour son lien.
          </SheetDescription>
        </SheetHeader>

        <div className="flex flex-col gap-4 px-4">
          <div>
            <p className="mb-2 text-sm font-medium text-foreground">Tuteurs actuels</p>
            {student?.guardians && student.guardians.length > 0 ? (
              <ul className="space-y-2">
                {student.guardians.map((g) => (
                  <li key={g.id} className="flex items-center justify-between rounded-md border p-2 text-sm">
                    <div>
                      <p className="font-medium">{g.full_name}</p>
                      <p className="text-muted-foreground">{g.phone}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <StatusBadge label={RELATIONSHIP_LABELS[g.relationship]} tone="info" />
                      {g.is_primary_contact && <StatusBadge label="Contact principal" tone="success" />}
                    </div>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-muted-foreground">Aucun tuteur associé pour le moment.</p>
            )}
          </div>

          <div className="space-y-2 border-t pt-4">
            <Label htmlFor="guardian_id">Associer un tuteur</Label>
            <select
              id="guardian_id"
              value={guardianId}
              onChange={(e) => setGuardianId(e.target.value)}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              <option value="">Sélectionner...</option>
              {(guardians?.data ?? []).map((g) => (
                <option key={g.id} value={g.id}>
                  {g.full_name} ({g.phone})
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="relationship">Lien de parenté</Label>
            <select
              id="relationship"
              value={relationship}
              onChange={(e) => setRelationship(e.target.value as GuardianRelationship)}
              className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
              {Object.entries(RELATIONSHIP_LABELS).map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </div>

          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={isPrimary}
              onChange={(e) => setIsPrimary(e.target.checked)}
              className="h-4 w-4 rounded border-input"
            />
            Définir comme contact principal
          </label>
        </div>

        <SheetFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Fermer
          </Button>
          <Button onClick={handleSubmit} disabled={!guardianId || linkGuardian.isPending}>
            {linkGuardian.isPending ? 'Association...' : 'Associer'}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
