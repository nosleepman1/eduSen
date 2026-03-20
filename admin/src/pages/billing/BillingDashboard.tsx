import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RiMoneyEuroCircleLine, RiGroupLine, RiArrowUpLine, RiSecurePaymentLine, RiBankCardLine, RiPulseLine } from 'react-icons/ri';
import { Line, Bar } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Filler,
  Legend,
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Filler,
  Legend
);

export default function BillingDashboard() {
  const chartData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [
      {
        fill: true,
        label: 'Revenus (FCFA)',
        data: [120000, 190000, 150000, 220000, 280000, 345000],
        borderColor: '#10b981', // Emerald 500
        backgroundColor: (context: any) => {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 300);
          gradient.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
          gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');
          return gradient;
        },
        tension: 0.4,
        pointBackgroundColor: '#10b981',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#10b981',
        pointRadius: 4,
        pointHoverRadius: 6,
      },
    ],
  };

  const barChartData = {
    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
    datasets: [
      {
        label: 'Paiements traités',
        data: [12, 19, 15, 22, 14, 5, 8],
        backgroundColor: '#6366f1', // Indigo 500
        borderRadius: 4,
      },
    ],
  };

  const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        mode: 'index' as const,
        intersect: false,
        backgroundColor: 'rgba(255, 255, 255, 0.9)',
        titleColor: '#1f2937',
        bodyColor: '#4b5563',
        borderColor: '#e5e7eb',
        borderWidth: 1,
        padding: 10,
        boxPadding: 4,
        usePointStyle: true,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: 'rgba(0, 0, 0, 0.04)', drawBorder: false },
        ticks: { color: '#9ca3af' },
      },
      x: {
        grid: { display: false, drawBorder: false },
        ticks: { color: '#9ca3af' },
      },
    },
    interaction: {
      mode: 'nearest' as const,
      axis: 'x' as const,
      intersect: false,
    },
  };

  return (
    <div className="space-y-8 pb-10">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h2 className="text-3xl font-bold tracking-tight bg-gradient-to-r from-slate-800 to-slate-500 bg-clip-text text-transparent">
            Tableau de bord financier
          </h2>
          <p className="text-muted-foreground mt-1 text-lg">
            Aperçu des performances de PayDunya et de la facturation des écoles.
          </p>
        </div>
        <div className="flex items-center gap-2 bg-emerald-50 text-emerald-700 px-4 py-2 rounded-full border border-emerald-100 text-sm font-medium shadow-sm">
          <RiPulseLine className="animate-pulse" /> Système en ligne
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <Card className="relative overflow-hidden border-none shadow-lg bg-gradient-to-br from-white to-slate-50 hover:shadow-xl transition-all duration-300 group">
          <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity group-hover:scale-110 duration-500">
            <RiMoneyEuroCircleLine className="w-24 h-24 text-emerald-600" />
          </div>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
            <CardTitle className="text-sm font-medium text-slate-500">Revenu Mensuel (MRR)</CardTitle>
            <div className="p-2 bg-emerald-100 rounded-lg">
              <RiMoneyEuroCircleLine className="h-5 w-5 text-emerald-600" />
            </div>
          </CardHeader>
          <CardContent className="relative z-10">
            <div className="text-3xl font-black text-slate-800">1 250 000 <span className="text-lg font-medium text-slate-400">FCFA</span></div>
            <p className="text-sm font-medium flex items-center mt-2 text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded-md">
              <RiArrowUpLine className="mr-1 h-4 w-4" />
              +15.3% <span className="text-slate-400 font-normal ml-1">vs mois dernier</span>
            </p>
          </CardContent>
        </Card>

        <Card className="relative overflow-hidden border-none shadow-lg bg-gradient-to-br from-white to-slate-50 hover:shadow-xl transition-all duration-300 group">
          <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity group-hover:scale-110 duration-500">
            <RiGroupLine className="w-24 h-24 text-blue-600" />
          </div>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
            <CardTitle className="text-sm font-medium text-slate-500">Abonnements Actifs</CardTitle>
            <div className="p-2 bg-blue-100 rounded-lg">
              <RiGroupLine className="h-5 w-5 text-blue-600" />
            </div>
          </CardHeader>
          <CardContent className="relative z-10">
            <div className="text-3xl font-black text-slate-800">145</div>
            <p className="text-sm font-medium flex items-center mt-2 text-blue-600 bg-blue-50 w-fit px-2 py-1 rounded-md">
              <RiArrowUpLine className="mr-1 h-4 w-4" />
              +12 écoles <span className="text-slate-400 font-normal ml-1">cette semaine</span>
            </p>
          </CardContent>
        </Card>

        <Card className="relative overflow-hidden border-none shadow-lg bg-gradient-to-br from-white to-slate-50 hover:shadow-xl transition-all duration-300 group">
          <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity group-hover:scale-110 duration-500">
            <RiSecurePaymentLine className="w-24 h-24 text-indigo-600" />
          </div>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
            <CardTitle className="text-sm font-medium text-slate-500">Paiements Réussis</CardTitle>
            <div className="p-2 bg-indigo-100 rounded-lg">
              <RiSecurePaymentLine className="h-5 w-5 text-indigo-600" />
            </div>
          </CardHeader>
          <CardContent className="relative z-10">
            <div className="text-3xl font-black text-slate-800">89%</div>
            <p className="text-sm font-medium flex items-center mt-2 text-indigo-600 bg-indigo-50 w-fit px-2 py-1 rounded-md">
              Taux de conversion global
            </p>
          </CardContent>
        </Card>

        <Card className="relative overflow-hidden border-none shadow-lg bg-gradient-to-br from-white to-slate-50 hover:shadow-xl transition-all duration-300 group">
          <div className="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity group-hover:scale-110 duration-500">
            <RiBankCardLine className="w-24 h-24 text-amber-600" />
          </div>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
            <CardTitle className="text-sm font-medium text-slate-500">En attente / Échoués</CardTitle>
            <div className="p-2 bg-amber-100 rounded-lg">
              <RiBankCardLine className="h-5 w-5 text-amber-600" />
            </div>
          </CardHeader>
          <CardContent className="relative z-10">
            <div className="text-3xl font-black text-slate-800">11%</div>
            <p className="text-sm font-medium flex items-center mt-2 text-amber-600 bg-amber-50 w-fit px-2 py-1 rounded-md">
              Nécessite votre attention
            </p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
        <Card className="col-span-4 border-none shadow-lg bg-white overflow-hidden">
          <CardHeader className="border-b border-slate-100/50 bg-slate-50/50">
            <CardTitle className="text-xl">Évolution des Revenus</CardTitle>
            <CardDescription>Croissance du chiffre d'affaires sur les 6 derniers mois.</CardDescription>
          </CardHeader>
          <CardContent className="p-6">
            <div className="h-[300px] w-full">
              <Line options={commonOptions} data={chartData} />
            </div>
          </CardContent>
        </Card>
        
        <Card className="col-span-3 border-none shadow-lg bg-white overflow-hidden flex flex-col">
          <CardHeader className="border-b border-slate-100/50 bg-slate-50/50">
            <CardTitle className="text-xl">Activité Hebdomadaire</CardTitle>
            <CardDescription>Volume de transactions traitées par jour.</CardDescription>
          </CardHeader>
          <CardContent className="p-6 flex-1">
             <div className="h-[300px] w-full">
              <Bar options={{...commonOptions, plugins: { legend: { display: false } }}} data={barChartData} />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Transactions List */}
      <Card className="border-none shadow-lg bg-white overflow-hidden">
        <CardHeader className="border-b border-slate-100/50 bg-slate-50/50">
          <CardTitle className="text-xl">Dernières Transactions PayDunya</CardTitle>
          <CardDescription>Les paiements les plus récents effectués par les écoles.</CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="bg-slate-50 text-slate-500">
                <tr>
                  <th className="px-6 py-4 font-semibold">École</th>
                  <th className="px-6 py-4 font-semibold">Forfait</th>
                  <th className="px-6 py-4 font-semibold">Montant</th>
                  <th className="px-6 py-4 font-semibold">Date</th>
                  <th className="px-6 py-4 font-semibold">Statut</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {[
                  { school: "Lycée d'Excellence", plan: "Premium Annuel", amount: '150 000 FCFA', date: 'Il y a 2h', status: 'success' },
                  { school: "Groupe Scolaire Yalla", plan: "Standard Mensuel", amount: '50 000 FCFA', date: 'Aujourd\'hui, 10:45', status: 'success' },
                  { school: "École Les Pédagogues", plan: "Premium Trimestriel", amount: '45 000 FCFA', date: 'Hier', status: 'failed' },
                  { school: "Complexe Scolaire Keur Madior", plan: "Basic Annuel", amount: '120 000 FCFA', date: '15 Juin 2026', status: 'success' },
                  { school: "Institution Jeanne d'Arc", plan: "Premium Annuel", amount: '150 000 FCFA', date: '12 Juin 2026', status: 'pending' },
                ].map((tx, idx) => (
                  <tr key={idx} className="hover:bg-slate-50/80 transition-colors group">
                    <td className="px-6 py-4 font-medium text-slate-800">{tx.school}</td>
                    <td className="px-6 py-4 text-slate-500">{tx.plan}</td>
                    <td className="px-6 py-4 font-semibold text-slate-700">{tx.amount}</td>
                    <td className="px-6 py-4 text-slate-500">{tx.date}</td>
                    <td className="px-6 py-4">
                      <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${
                        tx.status === 'success' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' :
                        tx.status === 'failed' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/10' :
                        'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20'
                      }`}>
                        {tx.status === 'success' ? 'Complété' : tx.status === 'failed' ? 'Échoué' : 'En attente'}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
