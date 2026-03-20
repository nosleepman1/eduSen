import { Button } from '@/components/ui/button';
import { useSchoolSetup } from '../../hooks/useSchoolSetup';
import { FiCheckCircle, FiLoader } from 'react-icons/fi';

export default function CompleteSetup({ onBack }: { onBack: () => void }) {
  const { completeSetup, isCompletingSetup } = useSchoolSetup();

  const handleComplete = async () => {
    await completeSetup();
    // Le store sera mis à jour par le hook, ce qui redirigera vers le dashboard
  };

  return (
    <div className="flex flex-col items-center justify-center py-8 text-center space-y-6">
      <div className="bg-green-100 dark:bg-green-900/20 p-6 rounded-full">
        <FiCheckCircle className="w-16 h-16 text-green-600 dark:text-green-500" />
      </div>
      
      <div className="space-y-2 max-w-md">
        <h2 className="text-2xl font-bold text-primary">Presque terminé !</h2>
        <p className="text-muted-foreground">
          Toutes les informations ont été enregistrées avec succès. Cliquez sur le bouton ci-dessous pour finaliser la configuration et accéder à votre tableau de bord.
        </p>
      </div>

      <div className="flex justify-center gap-4 pt-8 w-full max-w-sm">
        <Button type="button" variant="outline" onClick={onBack} disabled={isCompletingSetup} className="flex-1">
          Retour
        </Button>
        <Button 
          onClick={handleComplete} 
          disabled={isCompletingSetup} 
          className="bg-primary text-primary-foreground shadow-lg shadow-primary/25 flex-1"
        >
          {isCompletingSetup && <FiLoader className="mr-2 h-4 w-4 animate-spin" />}
          Accéder au Dashboard
        </Button>
      </div>
    </div>
  );
}
