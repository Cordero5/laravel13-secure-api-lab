import { create } from 'axios';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

// The PC's LAN address changes with every DHCP lease,
// so reuse the host the phone already reached the Metro bundle on instead of a
// hardcoded address. Set EXPO_PUBLIC_API_HOST to override for a standalone build.
const API_HOST_FALLBACK = '192.168.1.23';
const API_PORT = process.env.EXPO_PUBLIC_API_PORT || '8080';
const TOKEN_STORAGE_KEY = 'boarding-house.tenant-token';

function normalizeHost(value) {
  if (!value) return null;

  return value
    .replace(/^https?:\/\//i, '')
    .replace(/\/+$/, '')
    .split(':')[0]
    .trim();
}

function resolveHost() {
  if (process.env.EXPO_PUBLIC_API_HOST) {
    const host = normalizeHost(process.env.EXPO_PUBLIC_API_HOST);
    if (host) return host;
  }

  // In the browser the app and the LAN proxy are on the same machine, so the
  // page's own hostname already routes to it. The LAN-ip logic below is only
  // meaningful for a phone loading the bundle over Wi-Fi.
  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.hostname) {
    return normalizeHost(window.location.hostname) || '127.0.0.1';
  }

  const hostUri = Constants.expoConfig?.hostUri;
  const host = hostUri ? normalizeHost(hostUri) : null;

  // Over USB the bundle host is loopback, which the phone cannot route to the PC.
  if (!host || host === 'localhost' || host === '127.0.0.1') return API_HOST_FALLBACK;

  return host;
}

const API = create({
  baseURL: Platform.OS === 'web' ? 'http://127.0.0.1:8000/api' : `http://${resolveHost()}:${API_PORT}/api`,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Keep the web session across reloads while limiting its lifetime to this tab.
function readStoredToken() {
  if (Platform.OS !== 'web' || typeof window === 'undefined') return null;
  return window.sessionStorage.getItem(TOKEN_STORAGE_KEY);
}

function storeToken(token) {
  if (Platform.OS !== 'web' || typeof window === 'undefined') return;
  if (token) {
    window.sessionStorage.setItem(TOKEN_STORAGE_KEY, token);
  } else {
    window.sessionStorage.removeItem(TOKEN_STORAGE_KEY);
  }
}

// The backend protects every route with auth, so each request has
// to carry the bearer token returned by /login or /register.
let accessToken = readStoredToken();
let onUnauthorized = null;

export function setToken(token) {
  accessToken = token || null;
  storeToken(accessToken);
}

export function getToken() {
  return accessToken;
}

export function clearToken() {
  accessToken = null;
  storeToken(null);
}

export function setUnauthorizedHandler(handler) {
  onUnauthorized = handler;
}

API.interceptors.request.use((config) => {
  if (accessToken) {
    config.headers = config.headers || {};
    config.headers.Authorization = `Bearer ${accessToken}`;
  }
  return config;
});

API.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearToken();
      if (onUnauthorized) onUnauthorized();
    }
    return Promise.reject(error);
  }
);

export default API;