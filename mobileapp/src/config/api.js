import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';

// Default host based on machine IP and platform
const DEFAULT_HOST = '192.168.2.45'; // Active local WiFi IP

export const getDefaultApiUrl = () => {
  return `http://${DEFAULT_HOST}:8000/api`;
};

export const getApiBaseUrl = async () => {
  try {
    const saved = await AsyncStorage.getItem('@hrms_api_url');
    return saved || getDefaultApiUrl();
  } catch {
    return getDefaultApiUrl();
  }
};

export const setApiBaseUrl = async (url) => {
  try {
    await AsyncStorage.setItem('@hrms_api_url', url);
  } catch (e) {
    console.error('Failed to save API URL', e);
  }
};
