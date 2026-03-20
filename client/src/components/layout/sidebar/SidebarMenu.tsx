import {
  RiHomeLine,
  RiTeamLine,
  RiUser3Line,
  RiBuildingLine,
  RiBookOpenLine,
  RiFileList3Line,
  RiVipCrownLine,
  RiGraduationCapLine,
  RiParentLine,
  RiMailSendLine,
  RiCalendarLine,
  RiCalendarEventLine,
  RiFlaskLine,
  RiCalendar2Line,
  RiStackLine,
  RiBookmarkLine,
  RiUserSettingsLine,
  RiTimeLine,
  RiTimerLine,
  RiPresentationLine,
  RiUserFollowLine,
  RiFileTextLine,
  RiClipboardLine,
  RiAwardLine,
  RiFilePaper2Line,
} from 'react-icons/ri';
import SidebarItem from './SidebarItem';

const MENU_ITEMS = [
  { name: 'Vue globale', href: '/', icon: RiHomeLine },
  { name: 'Personnel', href: '/staff', icon: RiTeamLine },
  { name: 'Enseignants', href: '/teachers', icon: RiGraduationCapLine },
  { name: 'Élèves', href: '/students', icon: RiUser3Line },
  { name: 'Parents', href: '/guardians', icon: RiParentLine },
  { name: 'Invitations', href: '/invitations', icon: RiMailSendLine },
  { name: 'Année scolaire', href: '/academic-years', icon: RiCalendarLine },
  { name: 'Périodes', href: '/academic-periods', icon: RiCalendarEventLine },
  { name: 'Spécialisations', href: '/specializations', icon: RiFlaskLine },
  { name: 'Calendriers', href: '/school-calendars', icon: RiCalendar2Line },
  { name: 'Niveaux', href: '/levels', icon: RiStackLine },
  { name: 'Classes', href: '/classes', icon: RiBuildingLine },
  { name: 'Matières', href: '/subjects', icon: RiBookOpenLine },
  { name: 'Programmes', href: '/curriculums', icon: RiBookmarkLine },
  { name: 'Inscriptions', href: '/enrollments', icon: RiFileList3Line },
  { name: 'Affectations', href: '/teaching-assignments', icon: RiUserSettingsLine },
  { name: 'Emplois du temps', href: '/timetables', icon: RiTimeLine },
  { name: 'Créneaux', href: '/schedule-entries', icon: RiTimerLine },
  { name: 'Séances', href: '/lesson-sessions', icon: RiPresentationLine },
  { name: 'Présences', href: '/attendance-records', icon: RiUserFollowLine },
  { name: 'Contenus de cours', href: '/lesson-contents', icon: RiFileTextLine },
  { name: 'Évaluations', href: '/evaluations', icon: RiClipboardLine },
  { name: 'Notes', href: '/grades', icon: RiAwardLine },
  { name: 'Bulletins', href: '/bulletins', icon: RiFilePaper2Line },
  { name: 'Abonnement', href: '/subscription', icon: RiVipCrownLine },
];

export default function SidebarMenu({ onMobileItemClick }: { onMobileItemClick?: () => void }) {
  return (
    <nav className="flex flex-1 flex-col mt-4 overflow-y-auto overflow-x-hidden no-scrollbar">
      <ul role="list" className="flex flex-1 flex-col gap-y-1">
        {MENU_ITEMS.map((item) => (
          <SidebarItem 
            key={item.name} 
            name={item.name} 
            href={item.href} 
            icon={item.icon} 
            onClickMobile={onMobileItemClick} 
          />
        ))}
      </ul>
    </nav>
  );
}
