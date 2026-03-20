import { TableCell, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { SubscriptionActions } from './SubscriptionActions';
import type { Subscription } from '@/types/subscription.types';

interface SubscriptionRowProps {
  subscription: Subscription;
  onView: (subscription: Subscription) => void;
}

export function SubscriptionRow({ subscription, onView }: SubscriptionRowProps) {
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
    <TableRow>
      <TableCell className="font-medium">{subscription.school?.name || 'N/A'}</TableCell>
      <TableCell>{subscription.plan_name_snapshot}</TableCell>
      <TableCell>{getStatusBadge(subscription.status)}</TableCell>
      <TableCell>{new Date(subscription.ends_at).toLocaleDateString()}</TableCell>
      <TableCell>{subscription.auto_renew ? 'Oui' : 'Non'}</TableCell>
      <TableCell className="text-right">
        <SubscriptionActions subscription={subscription} onView={() => onView(subscription)} />
      </TableCell>
    </TableRow>
  );
}
