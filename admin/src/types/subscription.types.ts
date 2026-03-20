export interface SubscriptionPlan {
  id: string;
  name: string;
  price: number;
  currency: string;
  type: string;
  duration: number;
}

export interface BillingCycle {
  id: string;
  name: string;
  duration_days: number;
}

export interface School {
  id: string;
  name: string;
  email: string;
  phone: string;
}

export interface Subscription {
  id: string;
  school_id: string;
  subscription_plan_id: string;
  billing_cycle_id: string | null;
  status: 'ACTIVE' | 'TRIAL' | 'TRIAL_EXPIRED' | 'EXPIRED' | 'CANCELLED' | 'SUSPENDED' | 'GRACE_PERIOD';
  starts_at: string;
  ends_at: string;
  grace_ends_at: string | null;
  auto_renew: boolean;
  cancelled_at: string | null;
  cancellation_reason: string | null;
  plan_name_snapshot: string;
  plan_price_snapshot: number;
  
  // Relations
  school?: School;
  plan?: SubscriptionPlan;
  billing_cycle?: BillingCycle;
}

export interface PaginatedSubscriptions {
  data: Subscription[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
