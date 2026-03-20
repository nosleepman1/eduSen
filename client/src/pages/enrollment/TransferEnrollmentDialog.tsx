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
import { useTransferEnrollment } from '@/hooks/useEnrollments';
import type { Enrollment } from '@/types/enrollment.types';

interface TransferEnrollmentDialogProps {
  enrollment: Enrollment | null;
  onOpenChange: (open: boolean) => void;
}

/**
 * The server refuses the current classroom as a destination (EnrollmentService::transfer)
 * — filtered out here too so the choice never looks selectable in the first
 * place, rather than letting the user pick it and see a 422.
 */
export function TransferEnrollmentDialog({ enrollment, onOpenChange }: TransferEnrollmentDialogProps) {
  const [classroomId, setClassroomId] = useState('');
  const { data: classrooms } = useClassroomList(1);
  const transfer = useTransferEnrollment(enrollment?.id ?? '');

  const destinations = (classrooms?.data ?? []).filter(
    (c) => c.status === 'ACTIVE' && c.id !== enrollment?.classroom_id
  );

  const handleSubmit = async () => {
    await transfer.mutateAsync({ classroom_id: classroomId });
    setClassroomId('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={enrollment !== null}
      onOpenChange={(open) => {
        if (!open) setClassroomId('');
        onOpenChange(open);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Transférer {enrollment?.student?.full_name}</DialogTitle>
          <DialogDescription>
            Clôture l'inscription actuelle ({enrollment?.classroom?.name}) et en crée une nouvelle dans la classe
            choisie.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="destination">Classe de destination</Label>
          <select
            id="destination"
            value={classroomId}
            onChange={(e) => setClassroomId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {destinations.map((c) => (
              <option key={c.id} value={c.id}>
                {c.full_name}
              </option>
            ))}
          </select>
        </div>

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={!classroomId || transfer.isPending}>
            {transfer.isPending ? 'Transfert...' : 'Transférer'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
