import { useMutation } from '@tanstack/react-query';
import { schoolService } from '../services/school.service';
import { toast } from 'sonner';
import { useAuthStore } from '../store/auth.store';

export const useSchoolSetup = () => {
  const { setSchoolSetupComplete } = useAuthStore();

  const basicInfoMutation = useMutation({
    mutationFn: (data: FormData) => schoolService.saveBasicInfo(data),
    onSuccess: (response) => {
      toast.success(response.message || 'Informations de base enregistrées');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la sauvegarde');
    },
  });

  const contactInfoMutation = useMutation({
    mutationFn: (data: Record<string, any>) => schoolService.saveContactInfo(data),
    onSuccess: (response) => {
      toast.success(response.message || 'Coordonnées enregistrées');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la sauvegarde');
    },
  });

  const completeSetupMutation = useMutation({
    mutationFn: () => schoolService.completeSetup(),
    onSuccess: (response) => {
      toast.success(response.message || 'Configuration terminée !');
      setSchoolSetupComplete();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la validation');
    },
  });

  return {
    saveBasicInfo: basicInfoMutation.mutateAsync,
    isSavingBasicInfo: basicInfoMutation.isPending,
    saveContactInfo: contactInfoMutation.mutateAsync,
    isSavingContactInfo: contactInfoMutation.isPending,
    completeSetup: completeSetupMutation.mutateAsync,
    isCompletingSetup: completeSetupMutation.isPending,
  };
};
