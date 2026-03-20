import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useSubscription } from '../../hooks/useSubscription';
import { RiCheckLine, RiGroupLine, RiUserStarLine } from 'react-icons/ri';
import { useSubscriptionStore } from '../../store/subscription.store';

export default function PricingPlans() {
  const { plansQuery, billingCyclesQuery, checkoutMutation } = useSubscription();
  const { currentSubscription } = useSubscriptionStore();
  const [selectedCycleId, setSelectedCycleId] = useState<string | null>(null);

  const [loadingPlanId, setLoadingPlanId] = useState<string | null>(null);

  if (plansQuery.isLoading || billingCyclesQuery.isLoading) {
    return (
      <div className="flex flex-col items-center justify-center py-20">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
        <p className="mt-4 text-muted-foreground animate-pulse">Chargement des forfaits...</p>
      </div>
    );
  }

  const plans = plansQuery.data || [];
  const cycles = billingCyclesQuery.data || [];

  // Default to the first cycle if none selected
  const activeCycleId = selectedCycleId || cycles[0]?.id;
  const activeCycle = cycles.find(c => c.id === activeCycleId);

  const handleSubscribe = (planId: string) => {
    if (!activeCycleId) return;
    
    setLoadingPlanId(planId);
    checkoutMutation.mutate({
      subscription_plan_id: planId,
      billing_cycle_id: activeCycleId,
    }, {
      onError: () => setLoadingPlanId(null)
    });
  };

  return (
    <div className="space-y-8">
      <div className="text-center max-w-2xl mx-auto">
        <h2 className="text-3xl font-bold tracking-tight mb-4">Des tarifs simples et transparents</h2>
        <p className="text-muted-foreground text-lg mb-8">
          Choisissez le forfait qui correspond à la taille de votre établissement.
        </p>
        
        {cycles.length > 0 && (
          <div className="inline-flex bg-muted p-1 rounded-lg">
            {cycles.map(cycle => (
              <button
                key={cycle.id}
                onClick={() => setSelectedCycleId(cycle.id)}
                className={`px-6 py-2.5 text-sm font-medium rounded-md transition-colors ${
                  activeCycleId === cycle.id 
                    ? 'bg-background shadow-sm text-foreground' 
                    : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {cycle.name}
              </button>
            ))}
          </div>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        {plans.map((plan) => {
          const isCurrentPlan = currentSubscription?.subscription_plan_id === plan.id;
          const basePrice = parseFloat(plan.price);
          const cycleMultiplier = activeCycle ? (activeCycle.duration_days / 30) : 1; // Approx
          const discount = activeCycle ? parseFloat(activeCycle.discount_percentage) : 0;
          
          // Calculate final price based on cycle and discount
          const rawPrice = basePrice * cycleMultiplier;
          const finalPrice = rawPrice - (rawPrice * (discount / 100));

          return (
            <Card key={plan.id} className={`relative flex flex-col ${isCurrentPlan ? 'border-primary shadow-md' : 'shadow-sm'}`}>
              {isCurrentPlan && (
                <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-primary text-primary-foreground px-3 py-1 rounded-full text-xs font-bold">
                  Forfait Actuel
                </div>
              )}
              <CardHeader className="text-center pb-4">
                <CardTitle className="text-xl">{plan.name}</CardTitle>
                <CardDescription className="min-h-10">{plan.description}</CardDescription>
                <div className="mt-4 flex items-baseline justify-center gap-x-2">
                  <span className="text-4xl font-bold tracking-tight text-foreground">
                    {finalPrice.toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FCFA
                  </span>
                  <span className="text-sm font-semibold text-muted-foreground">/{activeCycle?.name}</span>
                </div>
              </CardHeader>
              <CardContent className="flex-1">
                <ul className="space-y-3 text-sm text-muted-foreground">
                  {plan.max_students !== null && (
                    <li className="flex gap-x-3">
                      <RiGroupLine className="h-5 w-5 flex-none text-primary" aria-hidden="true" />
                      <span>{plan.max_students === 0 ? 'Élèves illimités' : `Jusqu'à ${plan.max_students} élèves`}</span>
                    </li>
                  )}
                  {plan.max_teachers !== null && (
                    <li className="flex gap-x-3">
                      <RiUserStarLine className="h-5 w-5 flex-none text-primary" aria-hidden="true" />
                      <span>{plan.max_teachers === 0 ? 'Professeurs illimités' : `Jusqu'à ${plan.max_teachers} professeurs`}</span>
                    </li>
                  )}
                  {plan.features?.map((feature) => (
                    <li key={feature.id} className="flex gap-x-3">
                      <RiCheckLine className="h-5 w-5 flex-none text-primary" aria-hidden="true" />
                      <span>{feature.name}</span>
                    </li>
                  ))}
                </ul>
              </CardContent>
              <CardFooter>
                <Button 
                  className="w-full transition-all" 
                  variant={isCurrentPlan ? "outline" : "default"}
                  disabled={isCurrentPlan || checkoutMutation.isPending}
                  onClick={() => handleSubscribe(plan.id)}
                >
                  {checkoutMutation.isPending && loadingPlanId === plan.id ? (
                    <div className="flex items-center gap-2">
                      <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                      Redirection...
                    </div>
                  ) : isCurrentPlan ? (
                    'Actuel'
                  ) : (
                    'Souscrire'
                  )}
                </Button>
              </CardFooter>
            </Card>
          );
        })}
      </div>
    </div>
  );
}
