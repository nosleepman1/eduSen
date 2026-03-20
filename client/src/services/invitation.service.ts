import api from '../api/axios';
import type { ApiEnvelope } from '@/lib/api.types';
import type { AcceptInvitationPayload, CreateInvitationPayload, Invitation } from '@/types/invitation.types';

export const invitationService = {
  /** No search/filter — InvitationController@index takes no Request. */
  list(page = 1) {
    return api.get<ApiEnvelope<Invitation[]>>('/invitations', { params: { page } });
  },

  create(payload: CreateInvitationPayload) {
    return api.post<ApiEnvelope<Invitation>>('/invitations', payload);
  },

  resend(id: string) {
    return api.post<ApiEnvelope<null>>(`/invitations/${id}/resend`);
  },

  cancel(id: string) {
    return api.post<ApiEnvelope<null>>(`/invitations/${id}/cancel`);
  },

  /** Hard delete — Invitation has no soft-delete, there is no restore. */
  remove(id: string) {
    return api.delete<ApiEnvelope<null>>(`/invitations/${id}`);
  },

  /**
   * Public route, no Authorization header needed — but axios.ts attaches one
   * from the store whenever a token is present. Harmless here (the server
   * route has no auth middleware to reject it), just worth knowing this isn't
   * a bare fetch.
   */
  accept(payload: AcceptInvitationPayload) {
    return api.post<ApiEnvelope<{ user: { email: string } }>>('/invitations/accept', payload);
  },
};
