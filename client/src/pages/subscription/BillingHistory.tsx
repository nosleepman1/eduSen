import { useQuery } from '@tanstack/react-query';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { subscriptionService } from '../../services/subscription.service';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { RiDownloadCloud2Line, RiHistoryLine } from 'react-icons/ri';

export default function BillingHistory() {
  const { data: subscriptionData, isLoading } = useQuery({
    queryKey: ['subscription', 'current'],
    queryFn: subscriptionService.getCurrentSubscription,
  });

  const invoices = subscriptionData?.invoices || [];

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-xl flex items-center gap-2">
            <RiHistoryLine className="text-muted-foreground" />
            Historique de Facturation
          </CardTitle>
          <CardDescription>Chargement de l'historique...</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  return (
    <Card className="mt-8 border-muted shadow-sm">
      <CardHeader>
        <CardTitle className="text-xl flex items-center gap-2">
          <RiHistoryLine className="text-primary" />
          Historique de Facturation
        </CardTitle>
        <CardDescription>Retrouvez ici toutes vos anciennes factures.</CardDescription>
      </CardHeader>
      <CardContent>
        {invoices.length === 0 ? (
          <div className="text-center py-8 text-muted-foreground bg-muted/20 rounded-lg">
            Aucun historique de facturation trouvé.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left border-collapse">
              <thead className="bg-muted/50 text-muted-foreground border-b border-border">
                <tr>
                  <th className="px-4 py-3 font-medium rounded-tl-lg">N° Facture</th>
                  <th className="px-4 py-3 font-medium">Date</th>
                  <th className="px-4 py-3 font-medium">Forfait</th>
                  <th className="px-4 py-3 font-medium">Montant</th>
                  <th className="px-4 py-3 font-medium">Statut</th>
                  <th className="px-4 py-3 font-medium text-right rounded-tr-lg">Facture</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {invoices.map((inv: any) => (
                  <tr key={inv.id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-4 py-4 font-mono text-xs">{inv.invoice_number || '-'}</td>
                    <td className="px-4 py-4">
                      {inv.created_at ? format(new Date(inv.created_at), 'dd MMM yyyy', { locale: fr }) : '-'}
                    </td>
                    <td className="px-4 py-4 font-medium">{inv.plan?.name || 'Inconnu'}</td>
                    <td className="px-4 py-4">
                      {inv.amount ? `${parseFloat(inv.amount).toLocaleString('fr-FR')} FCFA` : '-'}
                    </td>
                    <td className="px-4 py-4">
                      {inv.status === 'PAID' ? (
                        <span className="inline-flex items-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                          Payée
                        </span>
                      ) : inv.status === 'PENDING' ? (
                        <span className="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                          En attente
                        </span>
                      ) : (
                        <span className="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                          Échouée
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-4 text-right">
                      <button 
                        className="text-primary hover:text-primary/80 transition-colors disabled:opacity-50" 
                        title="Télécharger"
                        disabled={inv.status !== 'PAID'}
                        onClick={() => alert("Le téléchargement PDF sera bientôt disponible !")}
                      >
                        <RiDownloadCloud2Line className="w-5 h-5 inline-block" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
