import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { SchoolsService } from '../services/schools.service';
import { toast } from 'sonner';

export const useSchoolRequests = (page = 1) => {
  return useQuery({
    queryKey: ['schoolRequests', page],
    queryFn: () => SchoolsService.getSchoolRequests(page),
  });
};

export const useAcceptSchoolMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => SchoolsService.acceptSchoolRequest(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['schoolRequests'] });
      toast.success('School request approved successfully');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to approve school request');
    },
  });
};

export const useRejectSchoolMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) => SchoolsService.rejectSchoolRequest(id, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['schoolRequests'] });
      toast.success('School request rejected successfully');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to reject school request');
    },
  });
};
