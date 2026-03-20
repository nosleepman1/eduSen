import api from '../api/axios';
import type { LoginResponse, SelectSchoolResponse } from '../types/auth.types';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';
const API_HOST = API_URL.replace(/\/api\/v1\/?$/, '');

export const authService = {
  async getCsrfToken() {
    await api.get(`${API_HOST}/sanctum/csrf-cookie`);
  },

  async login(credentials: Record<string, string>) {
    await this.getCsrfToken();
    const response = await api.post<{ message: string; data: LoginResponse }>('/auth/login', credentials);
    return response.data;
  },

  async selectSchool(tempToken: string, schoolId: string) {
    const response = await api.post<{ message: string; data: SelectSchoolResponse }>(
      '/auth/select-school',
      { school_id: schoolId },
      { headers: { Authorization: `Bearer ${tempToken}` } }
    );
    return response.data;
  },

  async setPassword(data: Record<string, string>) {
    const response = await api.post('/auth/set-password', data);
    return response.data;
  },

  async logout() {
    const response = await api.post('/auth/logout');
    return response.data;
  }
};
