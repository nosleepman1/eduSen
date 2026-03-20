import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { invitationService } from '@/services/invitation.service';
import { extractMessage } from '@/lib/apiErrors';
import type { CreateInvitationPayload } from '@/types/invitation.types';

const INVITATIONS_KEY = ['invitations'] as const;

export function useInvitationList(page: number) {
  return useQuery({
    queryKey: [...INVITATIONS_KEY, page],
    queryFn: () => invitationService.list(page).then((res) => res.data),
  });
}

export function useCreateInvitation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: CreateInvitationPayload) => invitationService.create(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: INVITATIONS_KEY });
      toast.success('Invitation créée et envoyée avec succès.');
    },
    // No onError toast: called through InvitationFormSheet, which renders
    // field-level errors and toasts only what it can't attach to a field.
  });
}

export function useResendInvitation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => invitationService.resend(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: INVITATIONS_KEY });
      toast.success("L'invitation a été renvoyée.");
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useCancelInvitation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => invitationService.cancel(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: INVITATIONS_KEY });
      toast.success("L'invitation a été annulée.");
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}

export function useDeleteInvitation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: string) => invitationService.remove(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: INVITATIONS_KEY });
      toast.success("L'invitation a été supprimée.");
    },
    onError: (error) => toast.error(extractMessage(error)),
  });
}
