import { Link, useSearchParams } from 'react-router-dom';
import { RiCheckDoubleFill, RiArrowRightLine, RiLoader4Line } from 'react-icons/ri';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { useEffect, useState } from 'react';
import { useSubscription } from '../../hooks/useSubscription';

export default function PaymentSuccessPage() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const { verifyPaymentMutation } = useSubscription();
  const [verifying, setVerifying] = useState(false);
  const [verified, setVerified] = useState(false);

  useEffect(() => {
    if (token && !verifying && !verified) {
      setVerifying(true);
      verifyPaymentMutation.mutate(token, {
        onSuccess: () => {
          setVerifying(false);
          setVerified(true);
        },
        onError: () => {
          setVerifying(false);
        }
      });
    }
  }, [token]);

  return (
    <div className="flex items-center justify-center min-h-[70vh] p-4">
      <Card className="w-full max-w-md border-emerald-100 shadow-lg relative overflow-hidden">
        <div className="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-emerald-400 to-teal-500"></div>
        <CardHeader className="text-center pt-8">
          <div className="mx-auto bg-emerald-100 text-emerald-600 w-20 h-20 rounded-full flex items-center justify-center mb-4">
            {verifying ? (
              <RiLoader4Line className="w-10 h-10 animate-spin text-emerald-600" />
            ) : (
              <RiCheckDoubleFill className="w-10 h-10" />
            )}
          </div>
          <CardTitle className="text-2xl font-bold text-emerald-800">
            {verifying ? 'Vérification en cours...' : 'Paiement Réussi !'}
          </CardTitle>
          <CardDescription className="text-base mt-2">
            {verifying ? 'Nous confirmons votre paiement avec PayDunya.' : 'Votre abonnement a été activé avec succès.'}
          </CardDescription>
        </CardHeader>
        <CardContent className="text-center text-muted-foreground">
          {verifying ? (
            <p>Veuillez patienter quelques instants...</p>
          ) : (
            <p>
              Le paiement a bien été reçu. Vos nouvelles fonctionnalités sont maintenant disponibles sur votre espace école.
            </p>
          )}
          {token && (
            <p className="mt-4 text-xs font-mono bg-muted p-2 rounded text-muted-foreground/80">
              Réf: {token}
            </p>
          )}
        </CardContent>
        <CardFooter className="flex justify-center pb-8">
          <Button asChild className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-full px-8">
            <Link to="/subscription" className="flex items-center gap-2">
              Retour à l'abonnement
              <RiArrowRightLine />
            </Link>
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
}
