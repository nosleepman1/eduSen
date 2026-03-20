import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { authService } from '../services/auth.service';
import { useAuthStore } from '../store/auth.store';
import { toast } from 'sonner';
import { can as hasPermission, type LoginResponse } from '../types/auth.types';

export const useAuth = () => {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user, token, school, role, permissions, mustChangePassword, schoolSetupRequired, setAuth, clearAuth } =
    useAuthStore();

  const loginMutation = useMutation({
    mutationFn: (credentials: Record<string, string>) => authService.login(credentials),
    onSuccess: (response: { data: LoginResponse }) => {
      const result = response.data;

      if (result.type === 'super_admin') {
        toast.error("Ce compte n'est pas associé à un établissement.");
        return;
      }

      if (result.type === 'multi_school') {
        navigate('/select-school', { state: { tempToken: result.token, schools: result.schools } });
        return;
      }

      setAuth({
        user: result.user,
        token: result.token,
        school: result.school,
        role: result.role,
        permissions: result.permissions ?? [],
        mustChangePassword: result.mustChangePassword,
        schoolSetupRequired: result.schoolSetupRequired,
      });
      toast.success('Connexion réussie');
      navigate('/');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur de connexion');
    },
  });

  const selectSchoolMutation = useMutation({
    mutationFn: ({ tempToken, schoolId }: { tempToken: string; schoolId: string }) =>
      authService.selectSchool(tempToken, schoolId),
    onSuccess: (response) => {
      const result = response.data;
      setAuth({
        user: result.user,
        token: result.token,
        school: result.school,
        role: result.role,
        permissions: result.permissions ?? [],
        mustChangePassword: false,
        schoolSetupRequired: result.schoolSetupRequired,
      });
      toast.success('Connexion réussie');
      navigate('/');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la sélection de l\'établissement');
    },
  });

  const setPasswordMutation = useMutation({
    mutationFn: (data: Record<string, string>) => authService.setPassword(data),
    onSuccess: () => {
      toast.success('Mot de passe configuré avec succès');
      navigate('/login');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Erreur lors de la configuration');
    },
  });

  const logout = () => {
    authService.logout().finally(() => {
      clearAuth();
      queryClient.clear();
      navigate('/login');
    });
  };

  return {
    user,
    token,
    school,
    role,
    permissions,
    /**
     * Gate an action on a backend permission name (e.g. `personnel.create`).
     * Hides or disables the control instead of letting the API answer 403.
     */
    can: (permission: string) => hasPermission(permissions, permission),
    mustChangePassword,
    isAuthenticated: !!token,
    schoolSetupRequired,
    login: loginMutation.mutate,
    isLoggingIn: loginMutation.isPending,
    selectSchool: selectSchoolMutation.mutate,
    isSelectingSchool: selectSchoolMutation.isPending,
    setPassword: setPasswordMutation.mutate,
    isSettingPassword: setPasswordMutation.isPending,
    logout,
  };
};
