import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { SchoolRequest } from '@/types/school.types';
import { Badge } from '@/components/ui/badge';

interface SchoolRequestDetailsModalProps {
  request: SchoolRequest | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export function SchoolRequestDetailsModal({ request, open, onOpenChange }: SchoolRequestDetailsModalProps) {
  if (!request) return null;

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
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Détails de la demande</DialogTitle>
          <DialogDescription>
            Informations détaillées sur la demande d'inscription de l'école.
          </DialogDescription>
        </DialogHeader>
        
        <div className="grid gap-4 py-4">
          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Statut:</span>
            <div className="col-span-3">{getStatusBadge(request.status)}</div>
          </div>
          
          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">École:</span>
            <span className="col-span-3 text-sm">{request.school_name}</span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Contact:</span>
            <span className="col-span-3 text-sm">{request.firstname} {request.lastname}</span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Email:</span>
            <span className="col-span-3 text-sm">{request.email}</span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Téléphone:</span>
            <span className="col-span-3 text-sm">{request.phone}</span>
          </div>

          <div className="grid grid-cols-4 items-center gap-4">
            <span className="font-semibold text-right text-sm">Date:</span>
            <span className="col-span-3 text-sm">
              {new Date(request.created_at).toLocaleString()}
            </span>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
