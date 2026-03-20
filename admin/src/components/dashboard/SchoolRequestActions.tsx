import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useAcceptSchoolMutation, useRejectSchoolMutation } from '@/hooks/useSchoolRequests';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Loader2 } from 'lucide-react';

interface SchoolRequestActionsProps {
  requestId: string;
  status: string;
  onView: () => void;
}

export function SchoolRequestActions({ requestId, status, onView }: SchoolRequestActionsProps) {
  const [rejectOpen, setRejectOpen] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  
  const acceptMutation = useAcceptSchoolMutation();
  const rejectMutation = useRejectSchoolMutation();

  const handleAccept = () => {
    acceptMutation.mutate(requestId);
  };

  const handleReject = () => {
    if (rejectReason.length < 10) return;
    rejectMutation.mutate(
      { id: requestId, reason: rejectReason },
      { onSuccess: () => setRejectOpen(false) }
    );
  };

  return (
    <div className="flex gap-2 justify-end">
      <Button size="sm" variant="outline" onClick={onView}>
        Voir
      </Button>

      {status === 'PENDING' && (
        <>
          <Button 
            size="sm" 
            onClick={handleAccept} 
            disabled={acceptMutation.isPending || rejectMutation.isPending}
          >
            {acceptMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Accepter
          </Button>

          <Dialog open={rejectOpen} onOpenChange={setRejectOpen}>
            <DialogTrigger asChild>
              <Button size="sm" variant="destructive" disabled={acceptMutation.isPending || rejectMutation.isPending}>
                Rejeter
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Rejeter la demande d'inscription</DialogTitle>
                <DialogDescription>
                  Veuillez fournir un motif pour le rejet de cette demande. Minimum 10 caractères.
                </DialogDescription>
              </DialogHeader>
              <div className="py-4">
                <Input 
                  placeholder="Motif du rejet..." 
                  value={rejectReason}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setRejectReason(e.target.value)}
                />
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setRejectOpen(false)}>Annuler</Button>
                <Button 
                  variant="destructive" 
                  onClick={handleReject}
                  disabled={rejectReason.length < 10 || rejectMutation.isPending}
                >
                  {rejectMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  Confirmer le rejet
                </Button>
              </DialogFooter>
            </DialogContent>
      </Dialog>
        </>
      )}
    </div>
  );
}
