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
import { useAcademicYearList } from '@/hooks/useAcademicYears';
import { useDuplicateCurriculum } from '@/hooks/useCurriculums';
import { SKIP_REASON_LABELS, type DuplicateCurriculumResult } from '@/types/curriculum.types';

interface DuplicateCurriculumDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * CurriculumService::duplicate() maps levels by code between years and skips
 * a line (rather than failing the batch) when its level has no counterpart,
 * was archived, or the line already exists in the target year — a
 * best-effort starting point, not a guaranteed full copy. The result is
 * shown line-by-line so the director knows exactly what still needs manual
 * attention, rather than a bare "done" toast.
 */
export function DuplicateCurriculumDialog({ open, onOpenChange }: DuplicateCurriculumDialogProps) {
  const [fromYearId, setFromYearId] = useState('');
  const [toYearId, setToYearId] = useState('');
  const [result, setResult] = useState<DuplicateCurriculumResult | null>(null);

  const { data: years } = useAcademicYearList(1);
  const duplicate = useDuplicateCurriculum();

  const handleSubmit = async () => {
    const response = await duplicate.mutateAsync({ from_academic_year_id: fromYearId, to_academic_year_id: toYearId });
    setResult(response.data.data);
  };

  const handleClose = (isOpen: boolean) => {
    if (!isOpen) {
      setResult(null);
      setFromYearId('');
      setToYearId('');
    }
    onOpenChange(isOpen);
  };

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Dupliquer le programme vers une nouvelle année</DialogTitle>
          <DialogDescription>
            Copie chaque ligne d'une année vers une autre en associant les niveaux par code. Un
            point de départ à ajuster ensuite, pas une copie garantie complète.
          </DialogDescription>
        </DialogHeader>

        {!result ? (
          <div className="space-y-4 px-1">
            <div className="space-y-2">
              <Label htmlFor="from-year">Depuis</Label>
              <select
                id="from-year"
                value={fromYearId}
                onChange={(e) => setFromYearId(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
              >
                <option value="">Sélectionner...</option>
                {(years?.data ?? []).map((y) => (
                  <option key={y.id} value={y.id}>
                    {y.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="to-year">Vers</Label>
              <select
                id="to-year"
                value={toYearId}
                onChange={(e) => setToYearId(e.target.value)}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
              >
                <option value="">Sélectionner...</option>
                {(years?.data ?? []).map((y) => (
                  <option key={y.id} value={y.id}>
                    {y.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
        ) : (
          <div className="space-y-3 px-1 text-sm">
            <p>
              <strong>{result.created.length}</strong> ligne(s) créée(s), <strong>{result.skipped.length}</strong>{' '}
              ignorée(s).
            </p>
            {result.skipped.length > 0 && (
              <ul className="max-h-48 space-y-1 overflow-y-auto rounded-md border p-2">
                {result.skipped.map((s) => (
                  <li key={s.curriculum_id} className="text-muted-foreground">
                    {SKIP_REASON_LABELS[s.reason]}
                  </li>
                ))}
              </ul>
            )}
          </div>
        )}

        <DialogFooter>
          {!result ? (
            <>
              <Button variant="outline" onClick={() => handleClose(false)}>
                Annuler
              </Button>
              <Button onClick={handleSubmit} disabled={!fromYearId || !toYearId || fromYearId === toYearId || duplicate.isPending}>
                {duplicate.isPending ? 'Duplication...' : 'Dupliquer'}
              </Button>
            </>
          ) : (
            <Button onClick={() => handleClose(false)}>Fermer</Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
