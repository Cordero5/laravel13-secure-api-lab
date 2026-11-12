import axios from 'axios';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

// The PC's LAN address changes with every DHCP lease (it moved from .7 to .20),
// so reuse the host the phone already reached the Metro bundle on instead of a
// hardcoded address. Set EXPO_PUBLIC_API_HOST to override for a standalone build.
const API_HOST_FALLBACK = '192.168.1.20';
const API_PORT = '8080';

function resolveHost() {
  if (process.env.EXPO_PUBLIC_API_HOST) return process.env.EXPO_PUBLIC_API_HOST;

  // In the browser the app and the LAN proxy are on the same machine, so the
  // page's own hostname already routes to it. The LAN-ip logic below is only
  // meaningful for a phone loading the bundle over Wi-Fi.
  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.hostname) {
    return window.location.hostname;
  }

  const hostUri = Constants.expoConfig?.hostUri;
  const host = hostUri ? hostUri.split(':')[0] : null;

  // Over USB the bundle host is loopback, which the phone cannot route to the PC.
  if (!host || host === 'localhost' || host === '127.0.0.1') return API_HOST_FALLBACK;

  return host;
}

const API = axios.create({
  baseURL: `http://${resolveHost()}:${API_PORT}/api`,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// The backend protects every /api route with auth:sanctum, so each request has
// to carry the bearer token returned by /login or /register.
let accessToken = null;
let onUnauthorized = null;

export function setToken(token) {
  accessToken = token || null;
}

export function getToken() {
  return accessToken;
}

export function clearToken() {
  accessToken = null;
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
      accessToken = null;
      if (onUnauthorized) onUnauthorized();
    }
    return Promise.reject(error);
  }
);

export default API;