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
import { useClassroomList } from '@/hooks/useClassrooms';
import { useAcademicPeriodList } from '@/hooks/useAcademicPeriods';
import { useGenerateClassBulletins } from '@/hooks/useBulletins';

interface GenerateClassBulletinsDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/** RB-010: la génération de classe est mise en file d'attente — la réponse confirme la mise en file, pas que chaque bulletin existe déjà. */
export function GenerateClassBulletinsDialog({ open, onOpenChange }: GenerateClassBulletinsDialogProps) {
  const [classroomId, setClassroomId] = useState('');
  const [academicPeriodId, setAcademicPeriodId] = useState('');
  const { data: classrooms } = useClassroomList(1);
  const { data: periods } = useAcademicPeriodList(1);
  const generateClass = useGenerateClassBulletins();

  const reset = () => {
    setClassroomId('');
    setAcademicPeriodId('');
  };

  const handleSubmit = async () => {
    await generateClass.mutateAsync({ classroom_id: classroomId, academic_period_id: academicPeriodId });
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
          <DialogTitle>Générer les bulletins de la classe</DialogTitle>
          <DialogDescription>
            Génère un bulletin pour chaque élève inscrit dans la classe. Traitement en file d'attente.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="bulletin-classroom">Classe</Label>
          <select
            id="bulletin-classroom"
            value={classroomId}
            onChange={(e) => setClassroomId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {(classrooms?.data ?? []).map((c) => (
              <option key={c.id} value={c.id}>
                {c.full_name}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-2 px-1">
          <Label htmlFor="bulletin-class-period">Période</Label>
          <select
            id="bulletin-class-period"
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
          <Button onClick={handleSubmit} disabled={!classroomId || !academicPeriodId || generateClass.isPending}>
            {generateClass.isPending ? 'Mise en file...' : 'Générer'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
