export interface SubscriptionFeature {
  id: string;
  name: string;
  code: string;
  description: string | null;
  limit: number;
}

export interface SubscriptionPlan {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  price: string; // Changed from base_price
  is_active: boolean;
  max_students: number | null;
  max_teachers: number | null;
  features: SubscriptionFeature[];
}

export interface BillingCycle {
  id: string;
  name: string;
  code: string; // Added code
  duration_days: number; // Changed from duration_in_days
  discount_percentage: string;
  active: boolean;
}

export interface Subscription {
  id: string;
  school_id: string;
  subscription_plan_id: string;
  billing_cycle_id: string;
  starts_at: string; // ISO DateTime
  ends_at: string; // ISO DateTime
  status: 'ACTIVE' | 'EXPIRED' | 'CANCELLED' | 'SUSPENDED';
  price_paid: string;
  auto_renew: boolean;
  plan?: SubscriptionPlan;
  billingCycle?: BillingCycle;
}

export interface SubscriptionState {
  currentSubscription: Subscription | null;
  subscriptionHistory: Subscription[];
  invoices: any[]; // Or define proper Invoice type
  setSubscriptionData: (current: Subscription | null, history: Subscription[], invoices?: any[]) => void;
  clearSubscriptionData: () => void;
}
