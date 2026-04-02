import apiFetch from '@wordpress/api-fetch';

/**
 * Configured API fetch wrapper for NextPress REST endpoints.
 * Uses WordPress nonce authentication automatically.
 */

const API_NAMESPACE = 'npb/v1';

function getApiUrl(): string {
  return window.npbAdmin?.apiUrl ?? `/wp-json/${API_NAMESPACE}`;
}

interface ApiOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: Record<string, unknown>;
  signal?: AbortSignal;
}

export async function api<T = unknown>(endpoint: string, options: ApiOptions = {}): Promise<T> {
  const { method = 'GET', body, signal } = options;

  const fetchOptions: Record<string, unknown> = {
    path: `${API_NAMESPACE}${endpoint}`,
    method,
    signal,
  };

  if (body && method !== 'GET') {
    fetchOptions.data = body;
  }

  return apiFetch(fetchOptions) as Promise<T>;
}

export function apiGet<T = unknown>(endpoint: string, signal?: AbortSignal): Promise<T> {
  return api<T>(endpoint, { method: 'GET', signal });
}

export function apiPost<T = unknown>(endpoint: string, body: Record<string, unknown>): Promise<T> {
  return api<T>(endpoint, { method: 'POST', body });
}

export function apiPut<T = unknown>(endpoint: string, body: Record<string, unknown>): Promise<T> {
  return api<T>(endpoint, { method: 'PUT', body });
}

export function apiDelete<T = unknown>(endpoint: string): Promise<T> {
  return api<T>(endpoint, { method: 'DELETE' });
}
