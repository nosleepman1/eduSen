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
import { useEnrollmentList } from '@/hooks/useEnrollments';
import { useAcademicPeriodList } from '@/hooks/useAcademicPeriods';
import { useGenerateBulletin } from '@/hooks/useBulletins';

interface GenerateBulletinDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/** One bulletin for one élève on one période, computed from that élève's Grades for the période. */
export function GenerateBulletinDialog({ open, onOpenChange }: GenerateBulletinDialogProps) {
  const [enrollmentId, setEnrollmentId] = useState('');
  const [academicPeriodId, setAcademicPeriodId] = useState('');
  const { data: enrollments } = useEnrollmentList(1);
  const { data: periods } = useAcademicPeriodList(1);
  const generate = useGenerateBulletin();

  const reset = () => {
    setEnrollmentId('');
    setAcademicPeriodId('');
  };

  const handleSubmit = async () => {
    await generate.mutateAsync({ enrollment_id: enrollmentId, academic_period_id: academicPeriodId });
    reset();
    onOpenChange(false);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(o) => {
        if (!o) reset();
        onOpenChange(o);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Générer un bulletin</DialogTitle>
          <DialogDescription>Calcule les moyennes de l'élève à partir des notes de la période.</DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="bulletin-enrollment">Élève</Label>
          <select
            id="bulletin-enrollment"
            value={enrollmentId}
            onChange={(e) => setEnrollmentId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {(enrollments?.data ?? []).map((e) => (
              <option key={e.id} value={e.id}>
                {e.student?.full_name ?? e.id}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-2 px-1">
          <Label htmlFor="bulletin-period">Période</Label>
          <select
            id="bulletin-period"
            value={academicPeriodId}
            onChange={(e) => setAcademicPeriodId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {(periods?.data ?? []).map((p) => (
              <option key={p.id} value={p.id}>
                {p.name}
              </option>
            ))}
          </select>
        </div>

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={!enrollmentId || !academicPeriodId || generate.isPending}>
            {generate.isPending ? 'Génération...' : 'Générer'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
