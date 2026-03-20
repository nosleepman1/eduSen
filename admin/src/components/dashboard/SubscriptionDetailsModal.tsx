import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { Subscription } from '@/types/subscription.types';
import { Badge } from '@/components/ui/badge';

interface SubscriptionDetailsModalProps {
  subscription: Subscription | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function SubscriptionDetailsModal({ subscription, open, onOpenChange }: SubscriptionDetailsModalProps) {
  if (!subscription) return null;

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'ACTIVE':
        return <Badge className="bg-green-600 hover:bg-green-700">Actif</Badge>;
      case 'TRIAL':
        return <Badge variant="secondary" className="bg-blue-100 text-blue-800 hover:bg-blue-200">Essai</Badge>;
      case 'TRIAL_EXPIRED':
        return <Badge variant="outline" className="text-orange-600 border-orange-600">Essai expiré</Badge>;
      case 'EXPIRED':
        return <Badge variant="outline" className="text-gray-600 border-gray-600">Expiré</Badge>;
      case 'CANCELLED':
        return <Badge variant="destructive">Annulé</Badge>;
      case 'SUSPENDED':
        return <Badge variant="destructive" className="bg-red-800">Suspendu</Badge>;
      case 'GRACE_PERIOD':
        return <Badge variant="outline" className="bg-yellow-100 text-yellow-800">Délai de grâce</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Détails de l'abonnement</DialogTitle>
          <DialogDescription>
            Informations détaillées sur cet abonnement.
          </DialogDescription>
        </DialogHeader>
        
        <div className="grid gap-4 py-4">
          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Statut:</span>
            <div className="col-span-3">{getStatusBadge(subscription.status)}</div>
          </div>
          
          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">École:</span>
            <span className="col-span-3 text-sm">{subscription.school?.name || 'N/A'}</span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Plan:</span>
            <span className="col-span-3 text-sm">{subscription.plan_name_snapshot}</span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Prix:</span>
            <span className="col-span-3 text-sm">{subscription.plan_price_snapshot}</span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Début:</span>
            <span className="col-span-3 text-sm">
              {new Date(subscription.starts_at).toLocaleString()}
            </span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Fin:</span>
            <span className="col-span-3 text-sm">
              {new Date(subscription.ends_at).toLocaleString()}
            </span>
          </div>

          {subscription.cancellation_reason && (
            <div className="grid grid-cols-4 items-center gap-4">
              <span className="font-semibold text-right text-sm">Motif annulation:</span>
              <span className="col-span-3 text-sm text-red-600">{subscription.cancellation_reason}</span>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
