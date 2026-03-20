import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useSubscriptionStore } from '../../store/subscription.store';
import { differenceInDays, parseISO, format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useSubscription } from '../../hooks/useSubscription';
import { RiErrorWarningLine, RiCalendarCheckLine } from 'react-icons/ri';

export default function CurrentPlanCard() {
  const { currentSubscription } = useSubscriptionStore();
  const { cancelMutation, checkoutMutation } = useSubscription();

  if (!currentSubscription) {
    return (
      <Card className="shadow-sm border-orange-200 bg-orange-50/50">
        <CardContent className="p-6">
          <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                <RiErrorWarningLine className="h-6 w-6" />
              </div>
              <div>
                <h3 className="font-semibold text-lg text-orange-900">Aucun abonnement actif</h3>
                <p className="text-orange-700 text-sm">Veuillez souscrire à un forfait pour utiliser toutes les fonctionnalités de la plateforme.</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  const daysLeft = differenceInDays(parseISO(currentSubscription.ends_at), new Date());
  const isExpiringSoon = daysLeft < 7;
  const isExpired = daysLeft < 0;

  const handleRenew = () => {
    if (window.confirm("Voulez-vous vraiment renouveler votre abonnement ? Vous allez être redirigé vers la page de paiement sécurisée.")) {
      if (currentSubscription.subscription_plan_id && currentSubscription.billing_cycle_id) {
        checkoutMutation.mutate({ 
          subscription_plan_id: currentSubscription.subscription_plan_id,
          billing_cycle_id: currentSubscription.billing_cycle_id
        });
      } else {
        alert("Impossible de renouveler : informations de facturation manquantes. Veuillez choisir un forfait manuellement.");
      }
    }
  };

  const handleCancel = () => {
    if (window.confirm("Voulez-vous vraiment annuler votre abonnement ? Cette action prendra effet immédiatement.")) {
      cancelMutation.mutate({ reason: "Annulation par le directeur" });
    }
  };

  return (
    <Card className="shadow-sm">
      <CardHeader>
        <CardTitle>Votre Abonnement Actuel</CardTitle>
        <CardDescription>Gérez votre souscription et la facturation</CardDescription>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col lg:flex-row gap-8 justify-between items-start lg:items-center">
          <div className="space-y-4 flex-1">
            <div className="flex items-center gap-3">
              <h3 className="text-2xl font-bold">{currentSubscription.plan?.name}</h3>
              <span className={`px-2.5 py-0.5 rounded-full text-xs font-semibold ${
                currentSubscription.status === 'ACTIVE' ? 'bg-green-100 text-green-700' : 
                currentSubscription.status === 'CANCELLED' ? 'bg-red-100 text-red-700' : 
                'bg-gray-100 text-gray-700'
              }`}>
                {currentSubscription.status}
              </span>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="flex items-start gap-2">
                <RiCalendarCheckLine className="h-5 w-5 text-muted-foreground mt-0.5" />
                <div>
                  <p className="text-sm font-medium text-foreground">Cycle de facturation</p>
                  <p className="text-sm text-muted-foreground">{currentSubscription.billingCycle?.name || 'Inconnu'}</p>
                </div>
              </div>
              <div className="flex items-start gap-2">
                <RiErrorWarningLine className={`h-5 w-5 mt-0.5 ${isExpired ? 'text-red-500' : isExpiringSoon ? 'text-orange-500' : 'text-muted-foreground'}`} />
                <div>
                  <p className="text-sm font-medium text-foreground">Expiration</p>
                  <p className={`text-sm ${isExpired ? 'text-red-600 font-semibold' : isExpiringSoon ? 'text-orange-600 font-semibold' : 'text-muted-foreground'}`}>
                    {format(parseISO(currentSubscription.ends_at), 'dd MMMM yyyy', { locale: fr })}
                    {daysLeft > 0 && ` (${daysLeft} jours restants)`}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div className="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            {currentSubscription.status === 'ACTIVE' && (
              <>
                <Button 
                  variant="outline" 
                  className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                  onClick={handleCancel}
                  disabled={cancelMutation.isPending}
                >
                  Annuler l'abonnement
                </Button>
                <Button 
                  onClick={handleRenew}
                  disabled={checkoutMutation.isPending}
                >
                  {checkoutMutation.isPending ? 'Redirection...' : 'Renouveler'}
                </Button>
              </>
            )}
            {(currentSubscription.status === 'EXPIRED' || currentSubscription.status === 'CANCELLED') && (
              <Button onClick={handleRenew} disabled={checkoutMutation.isPending}>
                Réactiver
              </Button>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
