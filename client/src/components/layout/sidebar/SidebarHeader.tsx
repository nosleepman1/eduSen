import { FaSchool } from 'react-icons/fa';
import { useSidebar } from '../SidebarContext';

export default function SidebarHeader() {
  const { isCollapsed } = useSidebar();

  return (
    <div className="flex h-16 shrink-0 items-center px-4 mb-4 mt-2">
      <div className={`flex items-center gap-3 w-full ${isCollapsed ? 'justify-center' : 'justify-start'}`}>
        <div className="flex shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground p-2">
          <FaSchool className="h-6 w-6" />
        </div>
        {!isCollapsed && (
          <span className="font-bold text-lg text-primary truncate overflow-hidden transition-all duration-200">
            Mon École
          </span>
        )}
      </div>
    </div>
  );
}
