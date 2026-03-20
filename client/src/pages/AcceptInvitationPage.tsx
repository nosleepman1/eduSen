import { useSearchParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import * as z from 'zod';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { FaSchool } from 'react-icons/fa';
import { FiLoader } from 'react-icons/fi';
import { invitationService } from '@/services/invitation.service';
import { applyValidationErrors } from '@/lib/apiErrors';

const schema = z
  .object({
    password: z.string().min(8, 'Le mot de passe doit contenir au moins 8 caractères.'),
    password_confirmation: z.string().min(8, 'Veuillez confirmer le mot de passe.'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Les mots de passe ne correspondent pas.',
    path: ['password_confirmation'],
  });

type FormData = z.infer<typeof schema>;

/**
 * Public route (POST /invitations/accept has no auth middleware) — token
 * carries the entire authorization. On success the server returns the
 * created user/personnel, not a bearer token (verified in
 * InvitationController::accept): there is nothing to sign the visitor in
 * with, so the only next step is /login.
 */
export default function AcceptInvitationPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const token = searchParams.get('token');

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<FormData>({ resolver: zodResolver(schema) });

  const acceptMutation = useMutation({
    mutationFn: (data: FormData) =>
      invitationService.accept({ token: token as string, ...data }),
    onSuccess: () => {
      toast.success('Compte créé avec succès. Vous pouvez maintenant vous connecter.');
      navigate('/login');
    },
    onError: (error) => {
      const unattached = applyValidationErrors(error, setError, ['password', 'password_confirmation']);
      unattached.forEach((message) => toast.error(message));
    },
  });

  if (!token) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-muted/30">
        <Card className="w-full max-w-md border-primary/20 shadow-xl">
          <CardContent className="pt-6 text-center font-medium text-destructive">
            Lien d'invitation invalide. Veuillez vérifier l'URL reçue par email.
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary/10 via-background to-secondary/10 p-4">
      <div className="w-full max-w-md">
        <div className="mb-8 flex justify-center">
          <div className="rounded-2xl bg-primary p-3 shadow-lg shadow-primary/20">
            <FaSchool className="h-10 w-10 text-primary-foreground" />
          </div>
        </div>

        <Card className="border-primary/10 bg-card/95 shadow-2xl backdrop-blur-sm">
          <CardHeader className="space-y-1 text-center">
            <CardTitle className="text-2xl font-bold tracking-tight text-primary">
              Activer mon compte
            </CardTitle>
            <CardDescription className="text-muted-foreground">
              Choisissez un mot de passe pour finaliser votre inscription.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit((data) => acceptMutation.mutate(data))} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="password">Mot de passe</Label>
                <Input id="password" type="password" {...register('password')} placeholder="••••••••" />
                {errors.password && <p className="text-sm text-destructive">{errors.password.message}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirmer le mot de passe</Label>
                <Input
                  id="password_confirmation"
                  type="password"
                  {...register('password_confirmation')}
                  placeholder="••••••••"
                />
                {errors.password_confirmation && (
                  <p className="text-sm text-destructive">{errors.password_confirmation.message}</p>
                )}
              </div>

              <Button type="submit" className="w-full" disabled={acceptMutation.isPending}>
                {acceptMutation.isPending && <FiLoader className="mr-2 h-4 w-4 animate-spin" />}
                Activer mon compte
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
