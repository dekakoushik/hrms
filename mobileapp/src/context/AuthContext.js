import React, { createContext, useState, useEffect, useContext } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getApiBaseUrl, setApiBaseUrl } from '../config/api';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [apiUrl, setApiUrlState] = useState('');

  useEffect(() => {
    loadStoredAuth();
  }, []);

  const loadStoredAuth = async () => {
    try {
      const storedUrl = await getApiBaseUrl();
      setApiUrlState(storedUrl);

      const storedToken = await AsyncStorage.getItem('@hrms_token');
      const storedUser = await AsyncStorage.getItem('@hrms_user');

      if (storedToken && storedUser) {
        setToken(storedToken);
        setUser(JSON.parse(storedUser));
        // Verify token in background
        fetchProfile(storedToken, storedUrl);
      }
    } catch (e) {
      console.error('Error loading auth from storage', e);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchProfile = async (authToken, currentUrl) => {
    try {
      const url = currentUrl || apiUrl || (await getApiBaseUrl());
      const response = await fetch(`${url}/auth/me`, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${authToken}`,
        },
      });
      if (response.ok) {
        const data = await response.json();
        if (data.user) {
          setUser(data.user);
          await AsyncStorage.setItem('@hrms_user', JSON.stringify(data.user));
        }
      }
    } catch {
      // offline or network hiccup, use cached user
    }
  };

  const login = async (loginIdentifier, password) => {
    const url = apiUrl || (await getApiBaseUrl());
    const response = await fetch(`${url}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        login: loginIdentifier.trim(),
        password,
        device_name: 'expo_mobile_app',
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      const message =
        data.message ||
        (data.errors && Object.values(data.errors)[0]?.[0]) ||
        'Authentication failed. Please check credentials.';
      throw new Error(message);
    }

    if (data.success && data.token && data.user) {
      setToken(data.token);
      setUser(data.user);
      await AsyncStorage.setItem('@hrms_token', data.token);
      await AsyncStorage.setItem('@hrms_user', JSON.stringify(data.user));
      return data.user;
    }

    throw new Error('Unexpected response format from server');
  };

  const logout = async () => {
    try {
      if (token) {
        const url = apiUrl || (await getApiBaseUrl());
        await fetch(`${url}/auth/logout`, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        });
      }
    } catch (e) {
      console.warn('Logout network error', e);
    } finally {
      setToken(null);
      setUser(null);
      await AsyncStorage.removeItem('@hrms_token');
      await AsyncStorage.removeItem('@hrms_user');
    }
  };

  const updateProfile = async (profileData) => {
    if (!token) throw new Error('Not authenticated');

    const url = apiUrl || (await getApiBaseUrl());
    const response = await fetch(`${url}/auth/profile`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(profileData),
    });

    const data = await response.json();

    if (!response.ok) {
      const message =
        data.message ||
        (data.errors && Object.values(data.errors)[0]?.[0]) ||
        'Failed to update profile';
      throw new Error(message);
    }

    if (data.user) {
      setUser(data.user);
      await AsyncStorage.setItem('@hrms_user', JSON.stringify(data.user));
      return data.user;
    }

    throw new Error('Failed to update profile');
  };

  const changePassword = async (currentPassword, newPassword, confirmPassword) => {
    if (!token) throw new Error('Not authenticated');

    const url = apiUrl || (await getApiBaseUrl());
    const response = await fetch(`${url}/auth/change-password`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      }),
    });

    const data = await response.json();

    if (!response.ok) {
      const message =
        data.message ||
        (data.errors && Object.values(data.errors)[0]?.[0]) ||
        'Failed to change password';
      throw new Error(message);
    }

    return data;
  };

  const updateApiUrl = async (newUrl) => {
    await setApiBaseUrl(newUrl);
    setApiUrlState(newUrl);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isLoading,
        apiUrl,
        updateApiUrl,
        login,
        logout,
        updateProfile,
        changePassword,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
