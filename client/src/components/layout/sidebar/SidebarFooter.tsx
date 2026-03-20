import { RiSettings4Line, RiLogoutBoxRLine } from 'react-icons/ri';
import { NavLink } from 'react-router-dom';
import { useSidebar } from '../SidebarContext';
import { useAuth } from '../../../hooks/useAuth';
import { useSubscriptionStore } from '../../../store/subscription.store';
import { differenceInDays, parseISO } from 'date-fns';

export function SubscriptionCard() {
  const { isCollapsed } = useSidebar();
  const { currentSubscription } = useSubscriptionStore();
  
  if (!currentSubscription) {
    if (isCollapsed) return null;
    return (
      <div className="mx-4 mb-2 p-4 rounded-xl bg-muted/50 border border-border">
        <div className="flex items-center justify-between mb-2">
          <h4 className="font-semibold text-sm">Abonnement</h4>
          <div className="text-[10px] font-bold px-2 py-0.5 rounded-full text-muted-foreground bg-muted">
            Inactif
          </div>
        </div>
      </div>
    );
  }

  const daysLeft = differenceInDays(parseISO(currentSubscription.ends_at), new Date());
  
  let statusColor = "text-green-600 bg-green-100";
  let statusDot = "bg-green-500";
  
  if (daysLeft < 7) {
    statusColor = "text-red-600 bg-red-100";
    statusDot = "bg-red-500";
  } else if (daysLeft < 15) {
    statusColor = "text-orange-600 bg-orange-100";
    statusDot = "bg-orange-500";
  }

  if (isCollapsed) {
    return (
      <div className="mx-2 mb-2 p-2 rounded-lg bg-muted flex justify-center" title={`${currentSubscription.plan?.name}: ${daysLeft} jours restants`}>
        <div className={`w-3 h-3 rounded-full ${statusDot}`} />
      </div>
    );
  }

  return (
    <div className="mx-4 mb-2 p-4 rounded-xl bg-muted/50 border border-border">
      <div className="flex items-center justify-between mb-2">
        <h4 className="font-semibold text-sm">{currentSubscription.plan?.name || 'Premium'}</h4>
        <div className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${statusColor}`}>
          {currentSubscription.status === 'ACTIVE' ? 'Actif' : 'Inactif'}
        </div>
      </div>
      <div className="text-xs text-muted-foreground mt-1">
        Expiration :
      </div>
      <div className="text-sm font-medium mt-0.5">
        {daysLeft > 0 ? `${daysLeft} jours restants` : 'Expiré'}
      </div>
    </div>
  );
}

export function SettingsButton({ onClickMobile }: { onClickMobile?: () => void }) {
  const { isCollapsed } = useSidebar();
  const { logout } = useAuth();

  return (
    <div className="px-3 pb-4 space-y-1">
      <NavLink
        to="/settings"
        onClick={onClickMobile}
        className={({ isActive }) =>
          `group flex items-center rounded-md text-sm font-medium transition-all duration-200 ${
            isActive
              ? 'bg-primary/10 text-primary'
              : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
          } ${isCollapsed ? 'justify-center p-3' : 'p-2 px-3 gap-x-3'}`
        }
        title={isCollapsed ? "Paramètres" : undefined}
      >
        <RiSettings4Line 
          className={`shrink-0 transition-colors group-hover:text-foreground ${
            isCollapsed ? 'h-6 w-6' : 'h-5 w-5'
          }`} 
        />
        {!isCollapsed && <span className="truncate">Paramètres</span>}
      </NavLink>

      <button
        onClick={() => logout()}
        className={`w-full group flex items-center rounded-md text-sm font-medium transition-all duration-200 text-destructive hover:bg-destructive/10 ${
          isCollapsed ? 'justify-center p-3' : 'p-2 px-3 gap-x-3'
        }`}
        title={isCollapsed ? "Déconnexion" : undefined}
      >
        <RiLogoutBoxRLine 
          className={`shrink-0 transition-colors ${
            isCollapsed ? 'h-6 w-6' : 'h-5 w-5'
          }`} 
        />
        {!isCollapsed && <span className="truncate">Déconnexion</span>}
      </button>
    </div>
  );
}

export default function SidebarFooter({ onMobileItemClick }: { onMobileItemClick?: () => void }) {
  return (
    <div className="mt-auto flex flex-col pt-4 border-t border-border/50">
      <SubscriptionCard />
      <SettingsButton onClickMobile={onMobileItemClick} />
    </div>
  );
}
