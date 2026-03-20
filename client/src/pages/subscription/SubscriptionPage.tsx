import { RiVipCrownLine } from 'react-icons/ri';
import CurrentPlanCard from './CurrentPlanCard';
import PricingPlans from './PricingPlans';
import BillingHistory from './BillingHistory';

export default function SubscriptionPage() {
  return (
    <div className="space-y-10 pb-10">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
            <RiVipCrownLine className="h-6 w-6 text-primary" />
            Abonnement et Facturation
          </h2>
          <p className="text-muted-foreground mt-1">
            Gérez votre forfait, vos cycles de facturation et vos options.
          </p>
        </div>
      </div>

      <CurrentPlanCard />
      
      <hr className="border-border" />
      
      <PricingPlans />

      <hr className="border-border my-8" />

      <BillingHistory />
    </div>
  );
}
