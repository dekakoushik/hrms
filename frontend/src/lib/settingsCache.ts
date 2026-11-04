import { apiRequest } from './api';

export interface SettingsData {
  companies: any[];
  departments: any[];
  designations: any[];
  shifts: any[];
  locations: any[];
  smtp?: any;
}

let inMemoryCache: SettingsData | null = null;
let inMemoryTimestamp = 0;
const CACHE_TTL_MS = 60 * 1000; // 1 minute fresh TTL
let pendingPromise: Promise<SettingsData> | null = null;

export function getCachedSettings(): SettingsData | null {
  if (inMemoryCache && Array.isArray(inMemoryCache.companies)) return inMemoryCache;
  if (typeof window !== 'undefined') {
    try {
      const stored = sessionStorage.getItem('hrms_settings_overview');
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed && Array.isArray(parsed.companies) && Array.isArray(parsed.departments)) {
          inMemoryCache = parsed;
          return parsed;
        } else {
          sessionStorage.removeItem('hrms_settings_overview');
        }
      }
    } catch {
      // ignore storage error
    }
  }
  return null;
}

export function setCachedSettings(data: SettingsData) {
  inMemoryCache = data;
  inMemoryTimestamp = Date.now();
  if (typeof window !== 'undefined') {
    try {
      sessionStorage.setItem('hrms_settings_overview', JSON.stringify(data));
    } catch {
      // ignore storage quota error
    }
  }
}

export function invalidateSettingsCache() {
  inMemoryCache = null;
  inMemoryTimestamp = 0;
  pendingPromise = null;
  if (typeof window !== 'undefined') {
    try {
      sessionStorage.removeItem('hrms_settings_overview');
    } catch {
      // ignore
    }
  }
}

export async function fetchSettingsOverview(forceRefresh = false): Promise<SettingsData> {
  const now = Date.now();

  // If cached and fresh, return immediately
  if (!forceRefresh && inMemoryCache && now - inMemoryTimestamp < CACHE_TTL_MS) {
    return inMemoryCache;
  }

  // Deduplicate concurrent in-flight requests (so 3 components don't make 3 requests simultaneously)
  if (pendingPromise) {
    return pendingPromise;
  }

  pendingPromise = apiRequest<SettingsData>('/settings/overview')
    .then((data) => {
      setCachedSettings(data);
      pendingPromise = null;
      return data;
    })
    .catch((err) => {
      pendingPromise = null;
      // Fallback to stale cache if network fails
      const fallback = getCachedSettings();
      if (fallback) return fallback;
      throw err;
    });

  return pendingPromise;
}

