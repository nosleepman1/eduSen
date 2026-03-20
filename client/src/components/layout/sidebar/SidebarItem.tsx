import { NavLink } from 'react-router-dom';
import { useSidebar } from '../SidebarContext';
import type { IconType } from 'react-icons';

interface SidebarItemProps {
  name: string;
  href: string;
  icon: IconType;
  onClickMobile?: () => void;
}

export default function SidebarItem({ name, href, icon: Icon, onClickMobile }: SidebarItemProps) {
  const { isCollapsed } = useSidebar();

  return (
    <li>
      <NavLink
        to={href}
        onClick={onClickMobile}
        className={({ isActive }) =>
          `group flex items-center rounded-md text-sm leading-6 font-medium transition-all duration-200 relative ${
            isActive
              ? 'bg-primary/10 text-primary'
              : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
          } ${isCollapsed ? 'justify-center p-3 mx-2' : 'p-2 px-3 mx-3 gap-x-3'}`
        }
        title={isCollapsed ? name : undefined}
      >
        {({ isActive }) => (
          <>
            {isActive && !isCollapsed && (
              <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 bg-primary rounded-r-md" />
            )}
            
            <Icon 
              className={`shrink-0 transition-colors ${
                isActive ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground'
              } ${isCollapsed ? 'h-6 w-6' : 'h-5 w-5'}`} 
              aria-hidden="true" 
            />
            
            {!isCollapsed && (
              <span className="truncate transition-opacity duration-200">
                {name}
              </span>
            )}
          </>
        )}
      </NavLink>
    </li>
  );
}
