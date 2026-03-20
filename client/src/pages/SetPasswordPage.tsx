import { useSearchParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { useAuth } from '../hooks/useAuth';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FaSchool } from 'react-icons/fa';
import { FiLoader } from 'react-icons/fi';

const schema = z.object({
  password: z.string().min(8, 'Le mot de passe doit contenir au moins 8 caractères'),
  password_confirmation: z.string().min(8, 'Veuillez confirmer le mot de passe'),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Les mots de passe ne correspondent pas",
  path: ["password_confirmation"],
});

type FormData = z.infer<typeof schema>;

export default function SetPasswordPage() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const email = searchParams.get('email');
  
  const { setPassword, isSettingPassword } = useAuth();
  
  const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: zodResolver(schema),
  });

  const onSubmit = (data: FormData) => {
    if (!token || !email) return;
    setPassword({
      token,
      email,
      password: data.password,
      password_confirmation: data.password_confirmation,
    });
  };

  if (!token || !email) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-muted/30">
        <Card className="w-full max-w-md shadow-xl border-primary/20">
          <CardContent className="pt-6 text-center text-destructive font-medium">
            Lien invalide ou expiré. Veuillez vérifier l'URL reçue par email.
          </CardContent>
        </Card>
      </div>
    );
  }

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
            <CardTitle className="text-2xl font-bold tracking-tight text-primary">Configuration du compte</CardTitle>
            <CardDescription className="text-muted-foreground">
              Bienvenue ! Veuillez créer votre mot de passe pour accéder à votre espace école.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email" className="text-foreground/80">Adresse Email</Label>
                <Input id="email" value={email} disabled className="bg-muted/50 font-medium" />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="password">Nouveau mot de passe</Label>
                <Input 
                  id="password" 
                  type="password" 
                  {...register('password')} 
                  className="focus-visible:ring-secondary"
                  placeholder="••••••••"
                />
                {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirmer le mot de passe</Label>
                <Input 
                  id="password_confirmation" 
                  type="password" 
                  {...register('password_confirmation')} 
                  className="focus-visible:ring-secondary"
                  placeholder="••••••••"
                />
                {errors.password_confirmation && <p className="text-sm text-destructive">{errors.password_confirmation.message}</p>}
              </div>

              <Button 
                type="submit" 
                className="w-full bg-primary hover:bg-primary/90 text-primary-foreground shadow-lg shadow-primary/25" 
                disabled={isSettingPassword}
              >
                {isSettingPassword && <FiLoader className="mr-2 h-4 w-4 animate-spin" />}
                Configurer mon compte
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
