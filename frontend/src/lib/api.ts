import Cookies from 'js-cookie';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api';

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  is_active?: boolean;
}

export interface AuthResponse {
  message: string;
  token: string;
  user: User;
}

export async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {},
  token?: string
): Promise<T> {
  const authToken = token || Cookies.get('hrms_token');

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(options.headers as Record<string, string>),
  };

  if (authToken) {
    headers['Authorization'] = `Bearer ${authToken}`;
  }

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (response.status === 401) {
    Cookies.remove('hrms_token');
    Cookies.remove('hrms_user');
    if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/login')) {
      window.location.href = '/login?expired=1';
    }
    throw new Error('Your session has expired. Please sign in again.');
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    const errorMsg =
      data.message ||
      (data.errors ? Object.values(data.errors).flat().join(', ') : 'An error occurred');
    throw new Error(errorMsg);
  }

  return data as T;
}

