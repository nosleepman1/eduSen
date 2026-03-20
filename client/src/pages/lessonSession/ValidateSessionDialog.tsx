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
import { Input } from '@/components/ui/input';
import { useValidateSession } from '@/hooks/useLessonSessions';
import type { LessonSession, ValidateDecision } from '@/types/lessonSession.types';

interface ValidateSessionDialogProps {
  session: LessonSession | null;
  onOpenChange: (open: boolean) => void;
}

/**
 * The pedagogical lead's decision for a session that was never started
 * (spec's "Validation administrative"). A reason is only required when the
 * decision is Cancelled — the server enforces the same rule.
 */
export function ValidateSessionDialog({ session, onOpenChange }: ValidateSessionDialogProps) {
  const [decision, setDecision] = useState<ValidateDecision>('COMPLETED');
  const [reason, setReason] = useState('');
  const validate = useValidateSession();

  const handleSubmit = async () => {
    if (!session) return;
    await validate.mutateAsync({ id: session.id, payload: { decision, reason: reason || null } });
    setDecision('COMPLETED');
    setReason('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={session !== null}
      onOpenChange={(open) => {
        if (!open) {
          setDecision('COMPLETED');
          setReason('');
        }
        onOpenChange(open);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Validation administrative</DialogTitle>
          <DialogDescription>Cette séance n'a jamais été démarrée par l'enseignant.</DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="decision">Décision</Label>
          <select
            id="decision"
            value={decision}
            onChange={(e) => setDecision(e.target.value as ValidateDecision)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="COMPLETED">Cours réalisé</option>
            <option value="CANCELLED">Cours annulé</option>
            <option value="POSTPONED">Cours reporté</option>
          </select>
        </div>

        {decision === 'CANCELLED' && (
          <div className="space-y-2 px-1">
            <Label htmlFor="validate-reason">Motif (obligatoire)</Label>
            <Input id="validate-reason" value={reason} onChange={(e) => setReason(e.target.value)} />
          </div>
        )}

        <DialogFooter>
          <Button
            onClick={handleSubmit}
            disabled={(decision === 'CANCELLED' && !reason.trim()) || validate.isPending}
          >
            {validate.isPending ? 'Validation...' : 'Valider'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
