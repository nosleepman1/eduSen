import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'sonner';
import { useAuth } from './hooks/useAuth';
import { SidebarProvider } from './components/layout/SidebarContext';
import DashboardLayout from './components/layout/DashboardLayout';
import DashboardHome from './pages/dashboard/DashboardHome';
import SubscriptionPage from './pages/subscription/SubscriptionPage';
import PersonnelPage from './pages/personnel/PersonnelPage';
import TeacherPage from './pages/teacher/TeacherPage';
import StudentPage from './pages/student/StudentPage';
import GuardianPage from './pages/guardian/GuardianPage';

import LoginPage from './pages/LoginPage';
import SelectSchoolPage from './pages/SelectSchoolPage';
import SetPasswordPage from './pages/SetPasswordPage';
import AcceptInvitationPage from './pages/AcceptInvitationPage';
import InvitationPage from './pages/invitation/InvitationPage';
import AcademicYearPage from './pages/academic/AcademicYearPage';
import AcademicPeriodPage from './pages/academic/AcademicPeriodPage';
import SpecializationPage from './pages/academic/SpecializationPage';
import SchoolCalendarPage from './pages/academic/SchoolCalendarPage';
import SubjectPage from './pages/subject/SubjectPage';
import LevelPage from './pages/level/LevelPage';
import ClassroomPage from './pages/classroom/ClassroomPage';
import CurriculumPage from './pages/curriculum/CurriculumPage';
import EnrollmentPage from './pages/enrollment/EnrollmentPage';
import TeachingAssignmentPage from './pages/teachingAssignment/TeachingAssignmentPage';
import TimetablePage from './pages/timetable/TimetablePage';
import ScheduleEntryPage from './pages/scheduleEntry/ScheduleEntryPage';
import LessonSessionPage from './pages/lessonSession/LessonSessionPage';
import AttendanceRecordPage from './pages/attendanceRecord/AttendanceRecordPage';
import LessonContentPage from './pages/lessonContent/LessonContentPage';
import EvaluationPage from './pages/evaluation/EvaluationPage';
import GradePage from './pages/grade/GradePage';
import BulletinPage from './pages/bulletin/BulletinPage';
import PaymentSuccessPage from './pages/subscription/PaymentSuccessPage';
import PaymentCancelPage from './pages/subscription/PaymentCancelPage';

const queryClient = new QueryClient();

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated } = useAuth();
  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }
  return <>{children}</>;
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/select-school" element={<SelectSchoolPage />} />
          <Route path="/set-password" element={<SetPasswordPage />} />
          <Route path="/invitations/accept" element={<AcceptInvitationPage />} />
          
          <Route path="/" element={
            <ProtectedRoute>
              <SidebarProvider>
                <DashboardLayout />
              </SidebarProvider>
            </ProtectedRoute>
          }>
            <Route index element={<DashboardHome />} />
            <Route path="staff" element={<PersonnelPage />} />
            <Route path="teachers" element={<TeacherPage />} />
            <Route path="students" element={<StudentPage />} />
            <Route path="guardians" element={<GuardianPage />} />
            <Route path="invitations" element={<InvitationPage />} />
            <Route path="academic-years" element={<AcademicYearPage />} />
            <Route path="academic-periods" element={<AcademicPeriodPage />} />
            <Route path="specializations" element={<SpecializationPage />} />
            <Route path="school-calendars" element={<SchoolCalendarPage />} />
            <Route path="subjects" element={<SubjectPage />} />
            <Route path="levels" element={<LevelPage />} />
            <Route path="classes" element={<ClassroomPage />} />
            <Route path="curriculums" element={<CurriculumPage />} />
            <Route path="enrollments" element={<EnrollmentPage />} />
            <Route path="teaching-assignments" element={<TeachingAssignmentPage />} />
            <Route path="timetables" element={<TimetablePage />} />
            <Route path="schedule-entries" element={<ScheduleEntryPage />} />
            <Route path="lesson-sessions" element={<LessonSessionPage />} />
            <Route path="attendance-records" element={<AttendanceRecordPage />} />
            <Route path="lesson-contents" element={<LessonContentPage />} />
            <Route path="evaluations" element={<EvaluationPage />} />
            <Route path="grades" element={<GradePage />} />
            <Route path="bulletins" element={<BulletinPage />} />
            <Route path="subscription" element={<SubscriptionPage />} />
            <Route path="payment/success" element={<PaymentSuccessPage />} />
            <Route path="payment/cancel" element={<PaymentCancelPage />} />
            <Route path="settings" element={<div className="p-4">Paramètres</div>} />
          </Route>
          
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
      <Toaster position="top-right" richColors />
    </QueryClientProvider>
  );
}
