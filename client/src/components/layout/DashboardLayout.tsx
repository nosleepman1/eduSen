import { Outlet } from 'react-router-dom';
import Sidebar from './sidebar/Sidebar';
import TopNavigation from './TopNavigation';
import { useSidebar } from './SidebarContext';
import { useAuth } from '../../hooks/useAuth';
import SchoolSetupWizard from '../setup/SchoolSetupWizard';
import MustChangePasswordScreen from '../auth/MustChangePasswordScreen';
import { useSubscription } from '../../hooks/useSubscription';

export default function DashboardLayout() {
  const { isCollapsed } = useSidebar();
  const { mustChangePassword, schoolSetupRequired } = useAuth();

  // Fetch subscription data globally
  useSubscription();

  if (mustChangePassword) {
    return <MustChangePasswordScreen />;
  }

  if (schoolSetupRequired) {
    return <SchoolSetupWizard />;
  }

  return (
    <div className="min-h-screen bg-background">
      <Sidebar />
      <div 
        className={`flex flex-col transition-all duration-200 ease-in-out min-h-screen ${
          isCollapsed ? 'md:pl-20' : 'md:pl-64'
        }`}
      >
        <TopNavigation />
        <main className="flex-1">
          <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
