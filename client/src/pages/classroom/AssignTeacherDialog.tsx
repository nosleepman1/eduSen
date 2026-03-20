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
import { useAssignPrincipalTeacher } from '@/hooks/useClassrooms';
import type { Classroom } from '@/types/classroom.types';

interface AssignTeacherDialogProps {
  classroom: Classroom | null;
  onOpenChange: (open: boolean) => void;
}

/**
 * ClassroomResource only exposes main_teacher_id, no name — resolving it to a
 * person means cross-referencing the teacher list, same as here. The picker
 * is limited to the first page of /teachers (no search there either); a
 * school with many teachers may not find the right one without first
 * checking the Enseignants screen.
 *
 * The server enforces that the chosen personnel is an active TEACHER
 * (ClassroomService::assignPrincipalTeacher) — the picker only offers
 * teacher profiles, so a rejection here would only happen on a race.
 */
export function AssignTeacherDialog({ classroom, onOpenChange }: AssignTeacherDialogProps) {
  const [teacherId, setTeacherId] = useState('');
  const { data: teachers } = useTeacherList(1);
  const assign = useAssignPrincipalTeacher(classroom?.id ?? '');

  const handleSubmit = async (personnelId: string | null) => {
    await assign.mutateAsync(personnelId);
    onOpenChange(false);
  };

  return (
    <Dialog open={classroom !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Professeur principal — {classroom?.full_name}</DialogTitle>
          <DialogDescription>
            {classroom?.main_teacher_id
              ? 'Un professeur principal est déjà assigné.'
              : "Aucun professeur principal assigné pour le moment."}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="teacher">Enseignant</Label>
          <select
            id="teacher"
            value={teacherId}
            onChange={(e) => setTeacherId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {(teachers?.data ?? []).map((t) => (
              <option key={t.id} value={t.personnel_id}>
                {t.full_name} {t.speciality ? `(${t.speciality})` : ''}
              </option>
            ))}
          </select>
        </div>

        <DialogFooter>
          {classroom?.main_teacher_id && (
            <Button variant="outline" onClick={() => handleSubmit(null)} disabled={assign.isPending}>
              Retirer
            </Button>
          )}
          <Button onClick={() => handleSubmit(teacherId)} disabled={!teacherId || assign.isPending}>
            {assign.isPending ? 'Enregistrement...' : 'Assigner'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
