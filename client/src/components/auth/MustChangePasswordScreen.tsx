import { useAuth } from '../../hooks/useAuth';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FaSchool } from 'react-icons/fa';

export default function MustChangePasswordScreen() {
  const { logout } = useAuth();

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/10 via-background to-secondary/10 p-4">
      <div className="w-full max-w-md">
        <div className="flex justify-center mb-8">
          <div className="bg-primary p-3 rounded-2xl shadow-lg shadow-primary/20">
            <FaSchool className="h-10 w-10 text-primary-foreground" />
          </div>
        </div>

        <Card className="shadow-2xl border-primary/10 backdrop-blur-sm bg-card/95">
          <CardHeader className="space-y-1 text-center">
            <CardTitle className="text-2xl font-bold tracking-tight text-primary">Mot de passe requis</CardTitle>
            <CardDescription className="text-muted-foreground">
              Votre compte n'a pas encore de mot de passe défini. Consultez l'email d'invitation que vous avez reçu
              pour définir votre mot de passe, puis reconnectez-vous.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Button variant="outline" className="w-full" onClick={logout}>
              Retour à la connexion
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
