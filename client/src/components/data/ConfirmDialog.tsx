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

interface ConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  /** Defaults to "Confirmer"; pass e.g. "Archiver" to name the action. */
  confirmLabel?: string;
  cancelLabel?: string;
  /** Red button for destructive/hard-to-reverse actions (archive, delete). */
  destructive?: boolean;
  onConfirm: () => void | Promise<void>;
  isConfirming?: boolean;
}

/**
 * One confirmation pattern for every irreversible or high-impact action
 * (archive, restore, suspend...) instead of a bespoke dialog per screen.
 */
export function ConfirmDialog({
  open,
  onOpenChange,
  title,
  description,
  confirmLabel = 'Confirmer',
  cancelLabel = 'Annuler',
  destructive = false,
  onConfirm,
  isConfirming = false,
}: ConfirmDialogProps) {
  const [busy, setBusy] = useState(false);
  const pending = isConfirming || busy;

  const handleConfirm = async () => {
    setBusy(true);
    try {
      await onConfirm();
    } finally {
      setBusy(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>{description}</DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={pending}>
            {cancelLabel}
          </Button>
          <Button variant={destructive ? 'destructive' : 'default'} onClick={handleConfirm} disabled={pending}>
            {pending ? 'En cours...' : confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
