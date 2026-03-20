import { Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { RiCloseLine } from 'react-icons/ri';
import { useSidebar } from '../SidebarContext';
import SidebarHeader from './SidebarHeader';
import SidebarMenu from './SidebarMenu';
import SidebarFooter from './SidebarFooter';

export default function Sidebar() {
  const { isCollapsed, isMobileOpen, closeMobileSidebar } = useSidebar();

  const DesktopSidebar = (
    <div 
      className={`hidden md:flex md:flex-col md:fixed md:inset-y-0 z-40 bg-card border-r border-border transition-all duration-200 ease-in-out ${
        isCollapsed ? 'md:w-20' : 'md:w-64'
      }`}
    >
      <div className="flex grow flex-col overflow-y-auto no-scrollbar bg-card h-full pt-2">
        <SidebarHeader />
        <SidebarMenu />
        <SidebarFooter />
      </div>
    </div>
  );

  const MobileSidebar = (
    <Transition.Root show={isMobileOpen} as={Fragment}>
      <Dialog as="div" className="relative z-50 md:hidden" onClose={closeMobileSidebar}>
        <Transition.Child
          as={Fragment}
          enter="transition-opacity ease-linear duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="transition-opacity ease-linear duration-300"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-background/80 backdrop-blur-sm" />
        </Transition.Child>

        <div className="fixed inset-0 flex">
          <Transition.Child
            as={Fragment}
            enter="transition ease-in-out duration-300 transform"
            enterFrom="-translate-x-full"
            enterTo="translate-x-0"
            leave="transition ease-in-out duration-300 transform"
            leaveFrom="translate-x-0"
            leaveTo="-translate-x-full"
          >
            <Dialog.Panel className="relative mr-16 flex w-full max-w-xs flex-1">
              <Transition.Child
                as={Fragment}
                enter="ease-in-out duration-300"
                enterFrom="opacity-0"
                enterTo="opacity-100"
                leave="ease-in-out duration-300"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
              >
                <div className="absolute left-full top-0 flex w-16 justify-center pt-5">
                  <button type="button" className="-m-2.5 p-2.5" onClick={closeMobileSidebar}>
                    <span className="sr-only">Fermer le menu</span>
                    <RiCloseLine className="h-6 w-6 text-foreground" aria-hidden="true" />
                  </button>
                </div>
              </Transition.Child>
              
              {/* Le Sidebar lui-même en mode mobile est toujours "ouvert" (non collapsed) */}
              <div className="flex grow flex-col overflow-y-auto bg-card border-r border-border pb-4 pt-2 shadow-2xl">
                <div className="px-4">
                  <div className="flex items-center gap-3 w-full justify-start h-16 mt-2 mb-4">
                    <div className="flex shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground p-2">
                      <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
                    </div>
                    <span className="font-bold text-lg text-primary">Mon École</span>
                  </div>
                </div>
                <SidebarMenu onMobileItemClick={closeMobileSidebar} />
                <SidebarFooter onMobileItemClick={closeMobileSidebar} />
              </div>
            </Dialog.Panel>
          </Transition.Child>
        </div>
      </Dialog>
    </Transition.Root>
  );

  return (
    <>
      {MobileSidebar}
      {DesktopSidebar}
    </>
  );
}
