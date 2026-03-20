import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { RiFileList3Line, RiCheckDoubleLine, RiTimeLine } from 'react-icons/ri';
import { format, parseISO } from 'date-fns';
import { fr } from 'date-fns/locale';
import type { DashboardStats } from '../../services/dashboard.service';

interface RecentActivitiesProps {
  activities: DashboardStats['recent_activities'];
}

export default function RecentActivities({ activities }: RecentActivitiesProps) {
  if (!activities || activities.length === 0) {
    return (
      <Card className="border-none shadow-md bg-white">
        <CardHeader className="border-b border-slate-50 bg-slate-50/50 pb-4">
          <CardTitle className="text-lg font-bold text-slate-800">Activités Récentes</CardTitle>
          <CardDescription>Dernières factures et paiements</CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col items-center justify-center p-8 text-slate-400">
          <RiFileList3Line className="w-12 h-12 mb-3 text-slate-200" />
          <p>Aucune activité récente.</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="border-none shadow-md bg-white overflow-hidden">
      <CardHeader className="border-b border-slate-50 bg-slate-50/50 pb-4">
        <CardTitle className="text-lg font-bold text-slate-800">Activités Récentes</CardTitle>
        <CardDescription>Dernières factures et paiements de l'école</CardDescription>
      </CardHeader>
      <CardContent className="p-0">
        <ul className="divide-y divide-slate-100">
          {activities.map((activity, idx) => {
            const isPaid = activity.status === 'PAID';
            return (
              <li key={activity.id || idx} className="p-4 hover:bg-slate-50/80 transition-colors">
                <div className="flex space-x-4">
                  <div className={`mt-1 flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center ring-4 ring-white shadow-sm ${isPaid ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'}`}>
                    {isPaid ? <RiCheckDoubleLine className="h-5 w-5" aria-hidden="true" /> : <RiTimeLine className="h-5 w-5" aria-hidden="true" />}
                  </div>
                  <div className="flex-1 space-y-1">
                    <div className="flex items-center justify-between">
                      <h3 className="text-sm font-semibold text-slate-800">
                        Facture {activity.invoice_number || `#${activity.id}`}
                      </h3>
                      <p className="text-sm text-slate-500">
                        {activity.created_at ? format(parseISO(activity.created_at), 'dd MMM yyyy, HH:mm', { locale: fr }) : 'Récent'}
                      </p>
                    </div>
                    <p className="text-sm text-slate-600">
                      Souscription au forfait <span className="font-medium text-slate-700">{activity.subscription?.plan?.name || 'Inconnu'}</span> 
                      {activity.subscription?.billing_cycle && ` (${activity.subscription.billing_cycle.name})`}
                    </p>
                    <div className="flex items-center justify-between pt-2">
                      <span className="font-bold text-slate-800">
                        {new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(activity.amount)}
                      </span>
                      <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${isPaid ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20'}`}>
                        {isPaid ? 'Payée' : 'En attente'}
                      </span>
                    </div>
                  </div>
                </div>
              </li>
            );
          })}
        </ul>
      </CardContent>
    </Card>
  );
}
