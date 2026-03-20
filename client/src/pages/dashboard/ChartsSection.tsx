import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line, Bar } from 'react-chartjs-2';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import type { DashboardStats } from '../../services/dashboard.service';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

interface ChartsSectionProps {
  charts: DashboardStats['charts'];
}

export default function ChartsSection({ charts }: ChartsSectionProps) {
  const enrollmentsData = {
    labels: charts.enrollments.labels,
    datasets: [
      {
        fill: true,
        label: 'Inscriptions',
        data: charts.enrollments.data,
        borderColor: 'rgba(99, 102, 241, 1)', // Indigo 500
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        tension: 0.4,
        pointBackgroundColor: 'rgba(99, 102, 241, 1)',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(99, 102, 241, 1)',
        pointRadius: 3,
      },
    ],
  };

  const paymentsData = {
    labels: charts.payments.labels,
    datasets: [
      {
        label: 'Revenus (FCFA)',
        data: charts.payments.data,
        backgroundColor: 'rgba(16, 185, 129, 0.85)', // Emerald 500
        borderRadius: 4,
      },
    ],
  };

  const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        backgroundColor: 'rgba(255, 255, 255, 0.95)',
        titleColor: '#1e293b',
        bodyColor: '#475569',
        borderColor: '#e2e8f0',
        borderWidth: 1,
        padding: 12,
        boxPadding: 4,
        usePointStyle: true,
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.04)',
          drawBorder: false,
        },
        ticks: { color: '#94a3b8' }
      },
      x: {
        grid: {
          display: false,
          drawBorder: false,
        },
        ticks: { color: '#94a3b8' }
      }
    }
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
      <Card className="border-none shadow-md overflow-hidden bg-white">
        <CardHeader className="border-b border-slate-50 bg-slate-50/50 pb-4">
          <CardTitle className="text-lg font-bold text-slate-800">Évolution des Inscriptions</CardTitle>
          <CardDescription>Nouvelles inscriptions au cours de l'année</CardDescription>
        </CardHeader>
        <CardContent className="p-6">
          <div className="h-72 w-full">
            <Line data={enrollmentsData} options={commonOptions} />
          </div>
        </CardContent>
      </Card>

      <Card className="border-none shadow-md overflow-hidden bg-white">
        <CardHeader className="border-b border-slate-50 bg-slate-50/50 pb-4">
          <CardTitle className="text-lg font-bold text-slate-800">Paiements Mensuels</CardTitle>
          <CardDescription>Revenus générés par les abonnements</CardDescription>
        </CardHeader>
        <CardContent className="p-6">
          <div className="h-72 w-full">
            <Bar data={paymentsData} options={commonOptions} />
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
