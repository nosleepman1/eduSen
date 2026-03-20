import { useQuery } from '@tanstack/react-query';
import { dashboardService } from '../../services/dashboard.service';
import StatsCards from './StatsCards';
import ChartsSection from './ChartsSection';
import RecentActivities from './RecentActivities';
import QuickActions from './QuickActions';
import { Link } from 'react-router-dom';

export default function DashboardHome() {
  const { data: stats, isLoading, isError, error } = useQuery({
    queryKey: ['dashboard', 'stats'],
    queryFn: dashboardService.getStats,
    retry: (failureCount, error: any) => {
      // Don't retry if it's a 403 Forbidden
      if (error?.response?.status === 403) return false;
      return failureCount < 3;
    },
  });

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] space-y-4">
        <div className="h-10 w-10 animate-spin rounded-full border-4 border-primary/20 border-t-primary"></div>
        <p className="text-muted-foreground animate-pulse font-medium text-lg">Chargement de votre espace...</p>
      </div>
    );
  }

  if (isError || !stats) {
    const isForbidden = (error as any)?.response?.status === 403;
    
    if (isForbidden) {
      return (
        <div className="flex flex-col items-center justify-center min-h-[60vh] space-y-4">
          <div className="bg-amber-100 p-4 rounded-full text-amber-600 mb-2">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 className="text-2xl font-bold text-slate-800">Abonnement Inactif</h3>
          <p className="text-slate-600 text-center max-w-md">
            Votre abonnement a été désactivé ou est arrivé à expiration. Veuillez renouveler votre abonnement pour accéder à nouveau à votre tableau de bord.
          </p>
          <Link to="/subscription" className="mt-4 px-6 py-2 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors">
            Gérer mon abonnement
          </Link>
        </div>
      );
    }

    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh]">
        <p className="text-destructive font-medium text-lg bg-destructive/10 px-4 py-2 rounded-lg">
          Impossible de charger les données du tableau de bord.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-8 pb-10">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h2 className="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent">
            Vue Globale
          </h2>
          <p className="text-muted-foreground mt-1 text-base">
            Bienvenue sur votre espace de gestion scolaire.
          </p>
        </div>
      </div>

      <StatsCards counts={stats.counts} />
      <ChartsSection charts={stats.charts} />
      
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2">
          <RecentActivities activities={stats.recent_activities} />
        </div>
        <div>
          <QuickActions />
        </div>
      </div>
    </div>
  );
}
