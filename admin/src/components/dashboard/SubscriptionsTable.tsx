import { useState } from 'react';
import { Table, TableBody, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { SubscriptionRow } from './SubscriptionRow';
import { usePlatformSubscriptions } from '@/hooks/usePlatformSubscriptions';
import { Loader2 } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { SubscriptionDetailsModal } from './SubscriptionDetailsModal';
import type { Subscription } from '@/types/subscription.types';

export function SubscriptionsTable() {
  const { data, isLoading, isError, error } = usePlatformSubscriptions(1);
  const [selectedSubscription, setSelectedSubscription] = useState<Subscription | null>(null);

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="text-center py-12 text-destructive">
        <p>Impossible de charger les abonnements.</p>
        <p className="text-sm text-muted-foreground">{(error as any)?.message}</p>
      </div>
    );
  }

  const subscriptions = data?.data?.data || [];

  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle>Gestion des Abonnements</CardTitle>
          <CardDescription>Consultez et gérez les abonnements des écoles (actifs, suspendus, annulés).</CardDescription>
        </CardHeader>
        <CardContent>
          {subscriptions.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              Aucun abonnement trouvé.
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>École</TableHead>
                  <TableHead>Plan</TableHead>
                  <TableHead>Statut</TableHead>
                  <TableHead>Expire le</TableHead>
                  <TableHead>Renouv. Auto</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {subscriptions.map((subscription) => (
                  <SubscriptionRow 
                    key={subscription.id} 
                    subscription={subscription} 
                    onView={setSelectedSubscription} 
                  />
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
      
      <SubscriptionDetailsModal 
        subscription={selectedSubscription} 
        open={!!selectedSubscription} 
        onOpenChange={(open) => !open && setSelectedSubscription(null)} 
      />
    </>
  );
}
