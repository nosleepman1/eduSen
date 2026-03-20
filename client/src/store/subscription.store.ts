import { create } from 'zustand';
import type { SubscriptionState } from '../types/subscription.types';

export const useSubscriptionStore = create<SubscriptionState>((set) => ({
  currentSubscription: null,
  subscriptionHistory: [],
  invoices: [],
  setSubscriptionData: (current, history, invoices = []) =>
    set({
      currentSubscription: current,
      subscriptionHistory: history,
      invoices,
    }),
  clearSubscriptionData: () =>
    set({
      currentSubscription: null,
      subscriptionHistory: [],
      invoices: [],
    }),
}));
