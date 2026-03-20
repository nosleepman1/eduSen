import api from '../api/axios';
import type { LoginCredentials, AuthResponse } from '../types/auth.types';

export const AuthService = {
  async csrfCookie() {
    return api.get('/sanctum/csrf-cookie', { baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000' });
  },

  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    await this.csrfCookie();
    const response = await api.post<AuthResponse>('/api/v1/auth/login', credentials);
    return response.data;
  },

  async logout(): Promise<void> {
    // If the backend has a logout endpoint, call it here:
    // await api.post('/api/v1/auth/logout');
  },
};
