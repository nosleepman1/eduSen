export interface User {
  id: string;
  firstname: string;
  lastname: string;
  email: string;
  role: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface AuthResponse {
  data: {
    user: User;
    token?: string;
  };
  message?: string;
}
