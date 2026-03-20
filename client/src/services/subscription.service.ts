import axios from '../api/axios';
import type { Subscription, SubscriptionPlan, BillingCycle } from '../types/subscription.types';

export const subscriptionService = {
  getCurrentSubscription: async () => {
    const response = await axios.get<{ data: { current: Subscription | null; history: Subscription[]; invoices: any[] } }>(
      '/subscription/current'
    );
    return response.data.data;
  },

  getPlans: async () => {
    const response = await axios.get<{ data: SubscriptionPlan[] }>('/subscription/plans');
    return response.data.data;
  },

  getBillingCycles: async () => {
    const response = await axios.get<{ data: BillingCycle[] }>('/subscription/billing-cycles');
    return response.data.data;
  },

  subscribe: async (data: { subscription_plan_id: string; billing_cycle_id: string }) => {
    const response = await axios.post<{ data: { subscription: Subscription } }>(
      '/subscription/subscribe',
      data
    );
    return response.data.data.subscription;
  },

  renew: async (data: { subscription_id: string }) => {
    const response = await axios.post<{ data: { subscription: Subscription } }>(
      '/subscription/renew',
      data
    );
    return response.data.data.subscription;
  },

  cancel: async (data: { reason?: string }) => {
    const response = await axios.post<{ data: { subscription: Subscription } }>(
      '/subscription/cancel',
      data
    );
    return response.data.data.subscription;
  },

  checkout: async (data: { subscription_plan_id: string; billing_cycle_id: string }) => {
    const response = await axios.post<{ data: { checkout_url: string; invoice: any } }>(
      '/subscription/checkout',
      data
    );
    return response.data.data;
  },

  verifyPayment: async (token: string) => {
    const response = await axios.post(`/subscription/verify-payment?token=${token}`);
    return response.data;
  },
};
