import { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTeacherList } from '@/hooks/useTeachers';
import { useReassignTeachingAssignment } from '@/hooks/useTeachingAssignments';
import type { TeachingAssignment } from '@/types/teachingAssignment.types';

interface ReassignDialogProps {
  assignment: TeachingAssignment | null;
  onOpenChange: (open: boolean) => void;
}

/**
 * Closes the current assignment and opens a new one for the replacement
 * teacher (TeachingAssignmentService::reassign) — past evaluations stay
 * linked to the original teacher, nothing here rewrites history.
 */
export function ReassignDialog({ assignment, onOpenChange }: ReassignDialogProps) {
  const [teacherId, setTeacherId] = useState('');
  const { data: teachers } = useTeacherList(1);
  const reassign = useReassignTeachingAssignment(assignment?.id ?? '');

  const candidates = (teachers?.data ?? []).filter((t) => t.id !== assignment?.teacher_id);

  const handleSubmit = async () => {
    await reassign.mutateAsync({ teacher_id: teacherId });
    setTeacherId('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={assignment !== null}
      onOpenChange={(open) => {
        if (!open) setTeacherId('');
        onOpenChange(open);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Réaffecter — {assignment?.classroom?.name}</DialogTitle>
          <DialogDescription>
            Clôture l'affectation de {assignment?.teacher?.full_name} et en crée une nouvelle pour l'enseignant
            choisi.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="new-teacher">Nouvel enseignant</Label>
          <select
            id="new-teacher"
            value={teacherId}
            onChange={(e) => setTeacherId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {candidates.map((t) => (
              <option key={t.id} value={t.id}>
                {t.full_name} {t.speciality ? `(${t.speciality})` : ''}
              </option>
            ))}
          </select>
        </div>

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={!teacherId || reassign.isPending}>
            {reassign.isPending ? 'Réaffectation...' : 'Réaffecter'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
