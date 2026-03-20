import { useState } from 'react';
import { Table, TableBody, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { SchoolRequestRow } from './SchoolRequestRow';
import { useSchoolRequests } from '@/hooks/useSchoolRequests';
import { Loader2 } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { SchoolRequestDetailsModal } from './SchoolRequestDetailsModal';
import type { SchoolRequest } from '@/types/school.types';

export function SchoolRequestsTable() {
  const { data, isLoading, isError, error } = useSchoolRequests(1);
  const [selectedRequest, setSelectedRequest] = useState<SchoolRequest | null>(null);

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-12">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="text-center py-12 text-destructive">
        <p>Impossible de charger les demandes.</p>
        <p className="text-sm text-muted-foreground">{(error as any)?.message}</p>
      </div>
    );
  }

  const requests = data?.data || [];

  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle>Demandes d'inscription</CardTitle>
          <CardDescription>Passez en revue et gérez les nouvelles demandes d'écoles.</CardDescription>
        </CardHeader>
        <CardContent>
          {requests.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              Aucune demande en attente.
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>École</TableHead>
                  <TableHead>Contact</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Statut</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {requests.map((request) => (
                  <SchoolRequestRow key={request.id} request={request} onView={setSelectedRequest} />
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
      <SchoolRequestDetailsModal 
        request={selectedRequest} 
        open={!!selectedRequest} 
        onOpenChange={(open) => !open && setSelectedRequest(null)} 
      />
    </>
  );
}
