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
import { useJustifyAttendance } from '@/hooks/useAttendanceRecords';
import type { AttendanceRecord } from '@/types/attendanceRecord.types';

interface JustifyDialogProps {
  record: AttendanceRecord | null;
  onOpenChange: (open: boolean) => void;
}

/** Only an Absent record can be justified — the server refuses a Present one. */
export function JustifyDialog({ record, onOpenChange }: JustifyDialogProps) {
  const [justification, setJustification] = useState('');
  const justify = useJustifyAttendance();

  const handleSubmit = async () => {
    if (!record) return;
    await justify.mutateAsync({ id: record.id, justification });
    setJustification('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={record !== null}
      onOpenChange={(open) => {
        if (!open) setJustification('');
        onOpenChange(open);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Justifier l'absence</DialogTitle>
          <DialogDescription>Un responsable pédagogique validera ou rejettera ensuite.</DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="justification">Justification</Label>
          <textarea
            id="justification"
            value={justification}
            onChange={(e) => setJustification(e.target.value)}
            placeholder="Certificat médical..."
            className="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
          />
        </div>

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={!justification.trim() || justify.isPending}>
            {justify.isPending ? 'Envoi...' : 'Soumettre'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
