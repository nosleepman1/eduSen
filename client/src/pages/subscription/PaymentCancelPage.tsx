import { Link } from 'react-router-dom';
import { RiCloseCircleFill, RiArrowGoBackLine } from 'react-icons/ri';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

export default function PaymentCancelPage() {
  return (
    <div className="flex items-center justify-center min-h-[70vh] p-4">
      <Card className="w-full max-w-md border-orange-100 shadow-lg relative overflow-hidden">
        <div className="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-orange-400 to-red-500"></div>
        <CardHeader className="text-center pt-8">
          <div className="mx-auto bg-orange-100 text-orange-500 w-20 h-20 rounded-full flex items-center justify-center mb-4">
            <RiCloseCircleFill className="w-10 h-10" />
          </div>
          <CardTitle className="text-2xl font-bold text-orange-800">Paiement Annulé</CardTitle>
          <CardDescription className="text-base mt-2">
            Le processus de paiement a été interrompu.
          </CardDescription>
        </CardHeader>
        <CardContent className="text-center text-muted-foreground">
          <p>
            Vous avez annulé la transaction ou une erreur s'est produite avec l'opérateur. Aucun montant n'a été débité de votre compte.
          </p>
        </CardContent>
        <CardFooter className="flex justify-center pb-8">
          <Button asChild variant="outline" className="rounded-full px-8 hover:bg-orange-50 hover:text-orange-600 border-orange-200 transition-colors">
            <Link to="/subscription" className="flex items-center gap-2">
              <RiArrowGoBackLine />
              Réessayer plus tard
            </Link>
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
}
