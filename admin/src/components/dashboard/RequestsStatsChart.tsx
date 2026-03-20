import { useMemo } from 'react';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title } from 'chart.js';
import { Pie, Bar } from 'react-chartjs-2';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useSchoolRequests } from '@/hooks/useSchoolRequests';
import { Loader2 } from 'lucide-react';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

export function RequestsStatsChart() {
  const { data, isLoading, isError } = useSchoolRequests(1);

  const stats = useMemo(() => {
    if (!data?.data) return { pending: 0, approved: 0, rejected: 0 };
    
    return data.data.reduce(
      (acc, req) => {
        if (req.status === 'PENDING') acc.pending++;
        if (req.status === 'APPROVED') acc.approved++;
        if (req.status === 'REJECTED') acc.rejected++;
        return acc;
      },
      { pending: 0, approved: 0, rejected: 0 }
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
        Impossible de charger les statistiques.
      </Card>
    );
  }

  const chartData = {
    labels: ['En attente', 'Approuvées', 'Rejetées'],
    datasets: [
      {
        label: 'Demandes',
        data: [stats.pending, stats.approved, stats.rejected],
        backgroundColor: [
          'rgba(234, 179, 8, 0.6)', // Yellow for pending
          'rgba(34, 197, 94, 0.6)', // Green for approved
          'rgba(239, 68, 68, 0.6)', // Red for rejected
        ],
        borderColor: [
          'rgba(234, 179, 8, 1)',
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
          <CardTitle>Répartition des statuts</CardTitle>
          <CardDescription>Vue globale des demandes d'inscription</CardDescription>
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
          <CardTitle>Aperçu chiffré</CardTitle>
          <CardDescription>Volume des demandes</CardDescription>
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
