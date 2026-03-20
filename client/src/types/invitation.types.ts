/**
 * Mirrors InvitationResource. This is the only IAM entity gated by `role:` in
 * routes/api/v1/school.php (OWNER,ADMIN) rather than `permission:` — the
 * `invitation.view`/`invitation.create` permissions exist in
 * DefaultPermissionsSeeder but guard no route. Screens for this domain check
 * `role` directly instead of using <Can permission="...">.
 */
export type InvitationRole =
  | 'OWNER'
  | 'ADMIN'
  | 'TEACHER'
  | 'SUPERVISOR'
  | 'ACCOUNTANT'
  | 'SECRETARY'
  | 'LIBRARIAN';

/**
 * No PHP enum backs this on the server — these are the literal values written
 * into the `status` column by InvitationService. `PENDING` can still be
 * expired in practice: Invitation::isExpired() also compares `expires_at`,
 * which is why `is_expired` (computed server-side) exists and should be
 * trusted over `status` alone.
 */
export type InvitationStatus = 'PENDING' | 'ACCEPTED' | 'EXPIRED' | 'CANCELLED';

export const INVITATION_ROLE_LABELS: Record<InvitationRole, string> = {
  OWNER: 'Propriétaire',
  ADMIN: 'Administrateur',
  TEACHER: 'Enseignant',
  SUPERVISOR: 'Surveillant',
  ACCOUNTANT: 'Comptable',
  SECRETARY: 'Secrétaire',
  LIBRARIAN: 'Bibliothécaire',
};

export const INVITATION_STATUS_LABELS: Record<InvitationStatus, string> = {
  PENDING: 'En attente',
  ACCEPTED: 'Acceptée',
  EXPIRED: 'Expirée',
  CANCELLED: 'Annulée',
};

export interface Invitation {
  id: string;
  email: string;
  firstname: string;
  lastname: string;
  role: InvitationRole;
  status: InvitationStatus;
  is_expired: boolean;
  expires_at: string | null;
  accepted_at: string | null;
  created_at: string | null;
  /** Only present when the server eager-loaded it (index and show do; store does not). */
  creator?: { id: string; full_name: string };
}

export interface CreateInvitationPayload {
  email: string;
  firstname: string;
  lastname: string;
  role: InvitationRole;
}

/**
 * POST /invitations/accept. Public, unauthenticated route — the token is the
 * only credential. On success the server returns the created user/personnel,
 * not a bearer token: the invitee must still log in afterwards.
 */
export interface AcceptInvitationPayload {
  token: string;
  password: string;
  password_confirmation: string;
}
