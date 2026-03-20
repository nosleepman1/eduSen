import { useMutation } from '@tanstack/react-query';
import { AuthService } from '../services/auth.service';
import type { LoginCredentials } from '../types/auth.types';
import { useAuthStore } from '../store/auth.store';
import { useNavigate } from 'react-router-dom';

export const useAuth = () => {
  const { user, isAuthenticated, login: setLoginState, logout: setLogoutState } = useAuthStore();
  const navigate = useNavigate();

  const loginMutation = useMutation({
    mutationFn: async (credentials: LoginCredentials) => {
      const response = await AuthService.login(credentials);
      setLoginState(response.data.user, response.data.token);
      return response;
    },
    onSuccess: () => {
      navigate('/');
    },
  });

  const logoutMutation = useMutation({
    mutationFn: () => AuthService.logout(),
    onSuccess: () => {
      setLogoutState();
      navigate('/login');
    },
  });

  return {
    user,
    isAuthenticated,
    login: loginMutation.mutateAsync,
    logout: logoutMutation.mutateAsync,
    isLoggingIn: loginMutation.isPending,
    loginError: loginMutation.error as any,
  };
};
