import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { AuthState } from '../types/auth.types';

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      school: null,
      role: null,
      permissions: [],
      mustChangePassword: false,
      schoolSetupRequired: false,
      setAuth: ({ user, token, school, role, permissions, mustChangePassword, schoolSetupRequired }) =>
        set({ user, token, school, role, permissions, mustChangePassword, schoolSetupRequired }),
      clearAuth: () =>
        set({
          user: null,
          token: null,
          school: null,
          role: null,
          permissions: [],
          mustChangePassword: false,
          schoolSetupRequired: false,
        }),
      setSchoolSetupComplete: () => set({ schoolSetupRequired: false }),
    }),
    {
      name: 'client-auth-storage',
    }
  )
);
