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
import { useLessonSessionList } from '@/hooks/useLessonSessions';
import { useBulkCreateAttendance } from '@/hooks/useAttendanceRecords';

interface BulkGenerateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

/**
 * One Absent record per active Enrollment of the session's classroom
 * (AttendanceRecordService::bulkCreate) — only allowed while the session is
 * InProgress, Completed or PendingValidation (RAR-004); an earlier session
 * will come back as a 422.
 */
export function BulkGenerateDialog({ open, onOpenChange }: BulkGenerateDialogProps) {
  const [sessionId, setSessionId] = useState('');
  const { data: sessions } = useLessonSessionList(1);
  const bulkCreate = useBulkCreateAttendance();

  const handleSubmit = async () => {
    await bulkCreate.mutateAsync(sessionId);
    setSessionId('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(o) => {
        if (!o) setSessionId('');
        onOpenChange(o);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Générer l'appel</DialogTitle>
          <DialogDescription>
            Crée un enregistrement Absent pour chaque élève actif de la classe. L'enseignant coche ensuite les
            présents.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="session">Séance</Label>
          <select
            id="session"
            value={sessionId}
            onChange={(e) => setSessionId(e.target.value)}
            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
          >
            <option value="">Sélectionner...</option>
            {(sessions?.data ?? []).map((s) => (
              <option key={s.id} value={s.id}>
                {s.session_date} — {s.scheduled_start_time.slice(0, 5)}
              </option>
            ))}
          </select>
        </div>

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={!sessionId || bulkCreate.isPending}>
            {bulkCreate.isPending ? 'Génération...' : 'Générer'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
