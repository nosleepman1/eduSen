import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useSuspendSubscriptionMutation, useCancelSubscriptionMutation } from '@/hooks/usePlatformSubscriptions';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Loader2 } from 'lucide-react';
import type { Subscription } from '@/types/subscription.types';

interface SubscriptionActionsProps {
  subscription: Subscription;
  onView: () => void;
}

export function SubscriptionActions({ subscription, onView }: SubscriptionActionsProps) {
  const [cancelOpen, setCancelOpen] = useState(false);
  const [cancelReason, setCancelReason] = useState('');
  
  const suspendMutation = useSuspendSubscriptionMutation();
  const cancelMutation = useCancelSubscriptionMutation();

  const handleSuspend = () => {
    if (confirm('Êtes-vous sûr de vouloir suspendre cet abonnement ?')) {
      suspendMutation.mutate(subscription.id);
    }
  };

  const handleCancel = () => {
    if (cancelReason.length < 10) return;
    cancelMutation.mutate(
      { id: subscription.id, reason: cancelReason },
      { onSuccess: () => setCancelOpen(false) }
    );
  };

  const isSuspendable = ['ACTIVE', 'TRIAL', 'GRACE_PERIOD'].includes(subscription.status);
  const isCancellable = ['ACTIVE', 'TRIAL', 'GRACE_PERIOD', 'SUSPENDED'].includes(subscription.status);

  return (
    <div className="flex gap-2 justify-end">
      <Button size="sm" variant="outline" onClick={onView}>
        Voir
      </Button>

      {isSuspendable && (
        <Button 
          size="sm" 
          variant="secondary"
          onClick={handleSuspend} 
          disabled={suspendMutation.isPending}
        >
          {suspendMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Suspendre
        </Button>
      )}

      {isCancellable && (
        <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
          <DialogTrigger asChild>
            <Button size="sm" variant="destructive" disabled={cancelMutation.isPending}>
              Annuler
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Annuler l'abonnement</DialogTitle>
              <DialogDescription>
                Veuillez fournir un motif pour l'annulation de cet abonnement. Minimum 10 caractères.
              </DialogDescription>
            </DialogHeader>
            <div className="py-4">
              <Input 
                placeholder="Motif de l'annulation..." 
                value={cancelReason}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setCancelReason(e.target.value)}
              />
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setCancelOpen(false)}>Fermer</Button>
              <Button 
                variant="destructive" 
                onClick={handleCancel}
                disabled={cancelReason.length < 10 || cancelMutation.isPending}
              >
                {cancelMutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Confirmer l'annulation
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
}
