import { SchoolRequestsTable } from '@/components/dashboard/SchoolRequestsTable';
import { RequestsStatsChart } from '@/components/dashboard/RequestsStatsChart';
import { SubscriptionsTable } from '@/components/dashboard/SubscriptionsTable';
import { SubscriptionsStatsChart } from '@/components/dashboard/SubscriptionsStatsChart';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function DashboardPage() {
  return (
    <div className="flex-1 space-y-6 p-8 pt-6">
      <div className="flex items-center justify-between space-y-2">
        <h2 className="text-3xl font-bold tracking-tight">Tableau de Bord</h2>
      </div>

      <Tabs defaultValue="demandes" className="w-full">
        <TabsList>
          <TabsTrigger value="demandes">Demandes d'inscription</TabsTrigger>
          <TabsTrigger value="abonnements">Abonnements</TabsTrigger>
        </TabsList>
        
        <TabsContent value="demandes" className="space-y-6 pt-4">
          <RequestsStatsChart />
          <SchoolRequestsTable />
        </TabsContent>

        <TabsContent value="abonnements" className="space-y-6 pt-4">
          <SubscriptionsStatsChart />
          <SubscriptionsTable />
        </TabsContent>
      </Tabs>
    </div>
  );
}
