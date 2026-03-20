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
import { useGeneratePeriods } from '@/hooks/useAcademicPeriods';
import { GENERATION_MODE_LABELS, type PeriodGenerationMode } from '@/types/academicPeriod.types';

interface GeneratePeriodsDialogProps {
  academicYearId: string | null;
  academicYearName: string;
  onOpenChange: (open: boolean) => void;
}

/**
 * Splits the whole academic year evenly into 2/3/4 periods
 * (AcademicPeriodService::generateDefaultPeriods) — a starting point to edit
 * afterwards, not a final schedule. Runs once per year in practice; calling it
 * again just adds another overlapping set, since generate() doesn't check for
 * periods that already exist.
 */
export function GeneratePeriodsDialog({ academicYearId, academicYearName, onOpenChange }: GeneratePeriodsDialogProps) {
  const [mode, setMode] = useState<PeriodGenerationMode>('TRIMESTER');
  const generate = useGeneratePeriods(academicYearId ?? '');

  return (
    <Dialog open={academicYearId !== null} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Générer les périodes de {academicYearName}</DialogTitle>
          <DialogDescription>
            Répartit uniformément les dates de l'année en périodes égales. À ajuster ensuite si besoin.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="mode">Découpage</Label>
          <select
            id="mode"
            value={mode}
            onChange={(e) => setMode(e.target.value as PeriodGenerationMode)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            {Object.entries(GENERATION_MODE_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={generate.isPending}>
            Annuler
          </Button>
          <Button
            onClick={async () => {
              await generate.mutateAsync(mode);
              onOpenChange(false);
            }}
            disabled={generate.isPending}
          >
            {generate.isPending ? 'Génération...' : 'Générer'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
