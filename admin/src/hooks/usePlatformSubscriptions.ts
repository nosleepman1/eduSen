import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../api/axios';
import type { PaginatedSubscriptions } from '../types/subscription.types';

export const usePlatformSubscriptions = (page = 1) => {
  return useQuery({
    queryKey: ['platform-subscriptions', page],
    queryFn: async () => {
      const response = await api.get<{ data: PaginatedSubscriptions }>(`/api/v1/platform/subscriptions?page=${page}`);
      return response.data;
    },
  });
};

export const useSuspendSubscriptionMutation = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (id: string) => {
      const response = await api.patch(`/api/v1/platform/subscriptions/${id}/suspend`);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['platform-subscriptions'] });
    },
  });
};

export const useCancelSubscriptionMutation = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async ({ id, reason }: { id: string; reason: string }) => {
      const response = await api.patch(`/api/v1/platform/subscriptions/${id}/cancel`, { reason });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['platform-subscriptions'] });
    },
  });
};
