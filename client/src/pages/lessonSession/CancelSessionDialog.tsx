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
import { useCancelSession } from '@/hooks/useLessonSessions';
import type { LessonSession } from '@/types/lessonSession.types';

interface CancelSessionDialogProps {
  session: LessonSession | null;
  onOpenChange: (open: boolean) => void;
}

/** Le motif est obligatoire côté serveur pour annuler une séance. */
export function CancelSessionDialog({ session, onOpenChange }: CancelSessionDialogProps) {
  const [reason, setReason] = useState('');
  const cancel = useCancelSession();

  const handleSubmit = async () => {
    if (!session) return;
    await cancel.mutateAsync({ id: session.id, payload: { reason } });
    setReason('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={session !== null}
      onOpenChange={(open) => {
        if (!open) setReason('');
        onOpenChange(open);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Annuler cette séance ?</DialogTitle>
          <DialogDescription>Le motif est obligatoire.</DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="reason">Motif</Label>
          <Input
            id="reason"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="Enseignant absent, grève, jour férié exceptionnel..."
          />
        </div>

        <DialogFooter>
          <Button variant="destructive" onClick={handleSubmit} disabled={!reason.trim() || cancel.isPending}>
            {cancel.isPending ? 'Annulation...' : 'Annuler la séance'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
