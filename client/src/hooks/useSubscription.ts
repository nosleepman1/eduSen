import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { subscriptionService } from '../services/subscription.service';
import { useSubscriptionStore } from '../store/subscription.store';
import { toast } from 'sonner';

export const useSubscription = () => {
  const queryClient = useQueryClient();
  const setSubscriptionData = useSubscriptionStore((state) => state.setSubscriptionData);

  const currentQuery = useQuery({
    queryKey: ['subscription', 'current'],
    queryFn: async () => {
      const data = await subscriptionService.getCurrentSubscription();
      setSubscriptionData(data.current, data.history);
      return data;
    },
  });

  const plansQuery = useQuery({
    queryKey: ['subscription', 'plans'],
    queryFn: subscriptionService.getPlans,
  });

  const billingCyclesQuery = useQuery({
    queryKey: ['subscription', 'billing-cycles'],
    queryFn: subscriptionService.getBillingCycles,
  });

  const subscribeMutation = useMutation({
    mutationFn: subscriptionService.subscribe,
    onSuccess: () => {
      toast.success("Abonnement souscrit avec succès.");
      queryClient.invalidateQueries({ queryKey: ['subscription', 'current'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Erreur lors de la souscription.");
    },
  });

  const renewMutation = useMutation({
    mutationFn: subscriptionService.renew,
    onSuccess: () => {
      toast.success("Abonnement renouvelé avec succès.");
      queryClient.invalidateQueries({ queryKey: ['subscription', 'current'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Erreur lors du renouvellement.");
    },
  });

  const cancelMutation = useMutation({
    mutationFn: subscriptionService.cancel,
    onSuccess: () => {
      toast.success("Abonnement annulé avec succès.");
      queryClient.invalidateQueries({ queryKey: ['subscription', 'current'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "Erreur lors de l'annulation.");
    },
  });

  const checkoutMutation = useMutation({
    mutationFn: subscriptionService.checkout,
    onSuccess: (data) => {
      if (data.checkout_url) {
        window.location.href = data.checkout_url;
      } else {
        toast.error('URL de paiement non disponible');
      }
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la préparation du paiement');
    },
  });

  const verifyPaymentMutation = useMutation({
    mutationFn: subscriptionService.verifyPayment,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['subscription', 'current'] });
    },
  });

  return {
    currentQuery,
    plansQuery,
    billingCyclesQuery,
    subscribeMutation,
    renewMutation,
    cancelMutation,
    checkoutMutation,
    verifyPaymentMutation,
  };
};
