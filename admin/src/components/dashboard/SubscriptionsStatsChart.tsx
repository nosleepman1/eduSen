import { useMemo } from 'react';
import { Pie, Bar } from 'react-chartjs-2';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { usePlatformSubscriptions } from '@/hooks/usePlatformSubscriptions';
import { Loader2 } from 'lucide-react';

export function SubscriptionsStatsChart() {
  const { data, isLoading, isError } = usePlatformSubscriptions(1);

  const stats = useMemo(() => {
    if (!data?.data?.data || !Array.isArray(data.data.data)) return { active: 0, inactive: 0 };
    
    return data.data.data.reduce(
      (acc, sub) => {
        if (['ACTIVE', 'TRIAL', 'GRACE_PERIOD'].includes(sub.status)) {
          acc.active++;
        } else {
          acc.inactive++;
        }
        return acc;
      },
      { active: 0, inactive: 0 }
    );
  }, [data]);

  if (isLoading) {
    return (
      <Card className="h-full min-h-[300px] flex items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </Card>
    );
  }

  if (isError) {
    return (
      <Card className="h-full min-h-[300px] flex items-center justify-center text-destructive">
        Impossible de charger les statistiques des abonnements.
      </Card>
    );
  }

  const chartData = {
    labels: ['Actifs (incl. Essai/Grâce)', 'Inactifs (Expiré/Suspendu/Annulé)'],
    datasets: [
      {
        label: 'Abonnements',
        data: [stats.active, stats.inactive],
        backgroundColor: [
          'rgba(34, 197, 94, 0.6)', // Green
          'rgba(239, 68, 68, 0.6)', // Red
        ],
        borderColor: [
          'rgba(34, 197, 94, 1)',
          'rgba(239, 68, 68, 1)',
        ],
        borderWidth: 1,
      },
    ],
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      <Card>
        <CardHeader>
          <CardTitle>Actifs vs Inactifs</CardTitle>
          <CardDescription>Répartition globale de l'état des abonnements</CardDescription>
        </CardHeader>
        <CardContent className="flex justify-center items-center h-[250px]">
          <Pie 
            data={chartData} 
            options={{ maintainAspectRatio: false }} 
          />
        </CardContent>
      </Card>
      
      <Card>
        <CardHeader>
          <CardTitle>Volume d'abonnements</CardTitle>
          <CardDescription>Aperçu chiffré</CardDescription>
        </CardHeader>
        <CardContent className="h-[250px]">
          <Bar 
            data={chartData}
            options={{ 
              maintainAspectRatio: false,
              plugins: { legend: { display: false } },
              scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }}
          />
        </CardContent>
      </Card>
    </div>
  );
}
