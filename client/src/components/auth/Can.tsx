import { useAuth } from '@/hooks/useAuth';

interface CanProps {
  /** Backend permission name, e.g. `personnel.create`. */
  permission: string;
  children: React.ReactNode;
  /** Rendered instead of children when the permission is missing. */
  fallback?: React.ReactNode;
}

/**
 * Renders its children only if the signed-in user holds the permission.
 *
 * Use the backend's own permission strings — the server gates each route with
 * the same name, so a typo here silently hides a control the user is entitled
 * to. This is a UX affordance, never a security boundary: the API remains the
 * authority and still answers 403.
 */
export function Can({ permission, children, fallback = null }: CanProps) {
  const { can } = useAuth();

  return <>{can(permission) ? children : fallback}</>;
}
