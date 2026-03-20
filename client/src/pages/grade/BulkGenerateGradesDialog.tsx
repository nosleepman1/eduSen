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
import { useEvaluationList } from '@/hooks/useEvaluations';
import { useBulkCreateGrades } from '@/hooks/useGrades';

interface BulkGenerateGradesDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/** One Pending Grade per active Enrollment of the evaluation's classroom — idempotent, existing Grades are never recreated. */
export function BulkGenerateGradesDialog({ open, onOpenChange }: BulkGenerateGradesDialogProps) {
  const [evaluationId, setEvaluationId] = useState('');
  const { data: evaluations } = useEvaluationList(1);
  const bulkCreate = useBulkCreateGrades();

  const handleSubmit = async () => {
    await bulkCreate.mutateAsync(evaluationId);
    setEvaluationId('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(o) => {
        if (!o) setEvaluationId('');
        onOpenChange(o);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Générer les notes</DialogTitle>
          <DialogDescription>
            Crée une note vide pour chaque élève inscrit dans la classe de l'évaluation.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="evaluation">Évaluation</Label>
          <select
            id="evaluation"
            value={evaluationId}
            onChange={(e) => setEvaluationId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {(evaluations?.data ?? []).map((ev) => (
              <option key={ev.id} value={ev.id}>
                {ev.title} — {ev.evaluation_date}
              </option>
            ))}
          </select>
        </div>

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={!evaluationId || bulkCreate.isPending}>
            {bulkCreate.isPending ? 'Génération...' : 'Générer'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
