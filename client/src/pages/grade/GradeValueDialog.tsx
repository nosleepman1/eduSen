import { useEffect, useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { useUpdateGrade } from '@/hooks/useGrades';
import { extractMessage } from '@/lib/apiErrors';
import { toast } from 'sonner';
import type { Grade, GradeStatus } from '@/types/grade.types';

interface GradeValueDialogProps {
  grade: Grade | null;
  onOpenChange: (open: boolean) => void;
}

/**
 * Plain component state rather than FormSheet/zod — a status+value combo
 * simple enough that a coerced-schema mismatch (the exact bug found on the
 * Evaluation screen's coefficient field) isn't worth risking again here.
 * RG-010/RG-009 (Absent forces value to 0, Dispensed clears it) are server
 * rules, not re-implemented client-side.
 */
export function GradeValueDialog({ grade, onOpenChange }: GradeValueDialogProps) {
  const [status, setStatus] = useState<GradeStatus>('GRADED');
  const [value, setValue] = useState('');
  const update = useUpdateGrade();

  useEffect(() => {
    if (grade) {
      setStatus(grade.status === 'PENDING' ? 'GRADED' : grade.status);
      setValue(grade.value !== null ? String(grade.value) : '');
    }
  }, [grade]);

  const handleSubmit = async () => {
    if (!grade) return;
    try {
      await update.mutateAsync({
        id: grade.id,
        payload: { status, value: status === 'GRADED' ? Number(value) : undefined },
      });
      onOpenChange(false);
    } catch (error) {
      toast.error(extractMessage(error));
    }
  };

  return (
    <Dialog open={grade !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Saisir la note</DialogTitle>
          <DialogDescription>La note doit être comprise entre 0 et le barème de l'évaluation.</DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="grade-status">Statut</Label>
          <select
            id="grade-status"
            value={status}
            onChange={(e) => setStatus(e.target.value as GradeStatus)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="GRADED">Notée</option>
            <option value="ABSENT">Absent</option>
            <option value="DISPENSED">Dispensé</option>
          </select>
        </div>

        {status === 'GRADED' && (
          <div className="space-y-2 px-1">
            <Label htmlFor="grade-value">Note</Label>
            <Input id="grade-value" type="number" step="0.5" min={0} value={value} onChange={(e) => setValue(e.target.value)} />
          </div>
        )}

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={(status === 'GRADED' && !value) || update.isPending}>
            {update.isPending ? 'Enregistrement...' : 'Enregistrer'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
