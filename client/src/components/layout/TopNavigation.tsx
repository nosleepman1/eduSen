import { RiMenuLine } from 'react-icons/ri';
import { useSidebar } from './SidebarContext';
import { useAuth } from '../../hooks/useAuth';

export default function TopNavigation() {
  const { toggleSidebar, toggleMobileSidebar } = useSidebar();
  const { user } = useAuth();

  return (
    <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-border bg-background px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 transition-all">
      <button
        type="button"
        className="-m-2.5 p-2.5 text-muted-foreground hover:text-foreground md:hidden"
        onClick={toggleMobileSidebar}
      >
        <span className="sr-only">Ouvrir le menu</span>
        <RiMenuLine className="h-6 w-6" aria-hidden="true" />
      </button>

      <button
        type="button"
        className="hidden md:block -m-2.5 p-2.5 text-muted-foreground hover:text-foreground"
        onClick={toggleSidebar}
      >
        <span className="sr-only">Réduire le menu</span>
        <RiMenuLine className="h-6 w-6" aria-hidden="true" />
      </button>

      <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 justify-end items-center">
        <div className="flex items-center gap-x-4 lg:gap-x-6">
          <div className="hidden lg:block lg:h-6 lg:w-px lg:bg-border" aria-hidden="true" />
          
          <div className="flex items-center gap-x-4">
            <span className="hidden lg:flex lg:items-center">
              <span className="ml-4 text-sm font-semibold leading-6 text-foreground" aria-hidden="true">
                {user?.firstname} {user?.lastname}
              </span>
            </span>
            <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
              {user?.firstname?.[0]}{user?.lastname?.[0]}
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
