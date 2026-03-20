import { Card, CardContent } from '@/components/ui/card';
import { RiFileList3Line, RiSearchLine, RiFilter3Line } from 'react-icons/ri';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

export default function InvoicesList() {
  const invoices = [
    { id: 'INV-001', school: 'Lycée d\'Excellence', amount: '150 000 FCFA', date: '07 Jui 2026', status: 'PAID' },
    { id: 'INV-002', school: 'Groupe Scolaire Yalla', amount: '50 000 FCFA', date: '06 Jui 2026', status: 'PAID' },
    { id: 'INV-003', school: 'Complexe Éducatif', amount: '250 000 FCFA', date: '05 Jui 2026', status: 'PENDING' },
    { id: 'INV-004', school: 'École Bilingue', amount: '50 000 FCFA', date: '04 Jui 2026', status: 'FAILED' },
    { id: 'INV-005', school: 'Institut Polytechnique', amount: '150 000 FCFA', date: '02 Jui 2026', status: 'PAID' },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
            <RiFileList3Line className="text-indigo-500" />
            Toutes les factures
          </h2>
          <p className="text-muted-foreground mt-1">
            Gérez et suivez les transactions de toutes les écoles.
          </p>
        </div>
        
        <div className="flex items-center gap-2">
          <div className="relative">
            <RiSearchLine className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input type="search" placeholder="Rechercher une école..." className="pl-8 w-[250px] bg-white" />
          </div>
          <Button variant="outline" className="flex items-center gap-2">
            <RiFilter3Line className="h-4 w-4" />
            Filtres
          </Button>
        </div>
      </div>

      <Card className="shadow-sm">
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left border-collapse">
              <thead className="bg-muted/50 text-muted-foreground border-b border-border">
                <tr>
                  <th className="px-6 py-4 font-medium">N° Facture</th>
                  <th className="px-6 py-4 font-medium">École</th>
                  <th className="px-6 py-4 font-medium">Date</th>
                  <th className="px-6 py-4 font-medium">Montant</th>
                  <th className="px-6 py-4 font-medium">Statut</th>
                  <th className="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {invoices.map((inv) => (
                  <tr key={inv.id} className="hover:bg-muted/30 transition-colors">
                    <td className="px-6 py-4 font-medium text-indigo-600">{inv.id}</td>
                    <td className="px-6 py-4 font-medium">{inv.school}</td>
                    <td className="px-6 py-4 text-muted-foreground">{inv.date}</td>
                    <td className="px-6 py-4 font-medium">{inv.amount}</td>
                    <td className="px-6 py-4">
                      {inv.status === 'PAID' ? (
                        <span className="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                          Payée
                        </span>
                      ) : inv.status === 'PENDING' ? (
                        <span className="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                          En attente
                        </span>
                      ) : (
                        <span className="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">
                          Échouée
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <Button variant="ghost" size="sm" className="text-primary">
                        Détails
                      </Button>
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
