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
import { useValidateBulletin } from '@/hooks/useBulletins';
import type { Bulletin } from '@/types/bulletin.types';

interface ValidateBulletinDialogProps {
  bulletin: Bulletin | null;
  onOpenChange: (open: boolean) => void;
}

export function ValidateBulletinDialog({ bulletin, onOpenChange }: ValidateBulletinDialogProps) {
  const [comment, setComment] = useState('');
  const validate = useValidateBulletin();

  const handleSubmit = async () => {
    if (!bulletin) return;
    await validate.mutateAsync({ id: bulletin.id, comment: comment || null });
    setComment('');
    onOpenChange(false);
  };

  return (
    <Dialog
      open={bulletin !== null}
      onOpenChange={(o) => {
        if (!o) setComment('');
        onOpenChange(o);
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Valider le bulletin</DialogTitle>
          <DialogDescription>Le conseil de classe peut ajouter un commentaire (facultatif).</DialogDescription>
        </DialogHeader>

        <div className="space-y-2 px-1">
          <Label htmlFor="council-comment">Commentaire du conseil de classe</Label>
          <textarea
            id="council-comment"
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            rows={3}
            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
          />
        </div>

        <DialogFooter>
          <Button onClick={handleSubmit} disabled={validate.isPending}>
            {validate.isPending ? 'Validation...' : 'Valider'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
