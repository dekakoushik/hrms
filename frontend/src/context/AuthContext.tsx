'use client';

import React, { createContext, useContext, useEffect, useState } from 'react';
import Cookies from 'js-cookie';
import { User, apiRequest } from '@/lib/api';
import { useRouter } from 'next/navigation';

interface AuthContextType {
  user: User | null;
  token: string | null;
  loading: boolean;
  login: (token: string, user: User) => void;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const router = useRouter();

  useEffect(() => {
    const savedToken = Cookies.get('hrms_token');
    const savedUser = Cookies.get('hrms_user');

    if (savedToken && savedUser) {
      try {
        setToken(savedToken);
        setUser(JSON.parse(savedUser));
      } catch {
        Cookies.remove('hrms_token');
        Cookies.remove('hrms_user');
      }
    }
    setLoading(false);
  }, []);

  const login = (authToken: string, authUser: User) => {
    Cookies.set('hrms_token', authToken, { expires: 7, sameSite: 'lax' });
    Cookies.set('hrms_user', JSON.stringify(authUser), { expires: 7, sameSite: 'lax' });
    setToken(authToken);
    setUser(authUser);
  };

  const logout = async () => {
    try {
      if (token) {
        await apiRequest('/logout', { method: 'POST' }, token);
      }
    } catch {
      // Ignore API logout error if network fails
    } finally {
      Cookies.remove('hrms_token');
      Cookies.remove('hrms_user');
      setToken(null);
      setUser(null);
      router.push('/login');
    }
  };

  return (
    <AuthContext.Provider value={{ user, token, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

