import { TableCell, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { SchoolRequestActions } from './SchoolRequestActions';
import type { SchoolRequest } from '@/types/school.types';

interface SchoolRequestRowProps {
  request: SchoolRequest;
  onView: (request: SchoolRequest) => void;
}

export function SchoolRequestRow({ request, onView }: SchoolRequestRowProps) {
  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'PENDING':
        return <Badge variant="secondary">En attente</Badge>;
      case 'APPROVED':
        return <Badge className="bg-green-600 hover:bg-green-700">Approuvée</Badge>;
      case 'REJECTED':
        return <Badge variant="destructive">Rejetée</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  return (
    <TableRow>
      <TableCell className="font-medium">{request.school_name}</TableCell>
      <TableCell>{request.firstname} {request.lastname}</TableCell>
      <TableCell>{request.email}</TableCell>
      <TableCell>{new Date(request.created_at).toLocaleDateString()}</TableCell>
      <TableCell>{getStatusBadge(request.status)}</TableCell>
      <TableCell className="text-right">
        <SchoolRequestActions requestId={request.id} status={request.status} onView={() => onView(request)} />
      </TableCell>
    </TableRow>
  );
}
