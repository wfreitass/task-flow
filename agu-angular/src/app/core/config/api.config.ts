/** Root URL shared by all frontend repositories that call the Symfony API. */
export const API_BASE_URL = 'http://localhost:8000/';

/** WebSocket shares the backend host and uses its dedicated path. */
export function realtimeUrl(): string {
  const url = new URL('/ws', API_BASE_URL);
  url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:';
  return url.toString();
}

/** Builds an API URL without coupling a feature to the host or the `/api` prefix. */
export function apiUrl(path: string): string {
  return new URL(`api/${path.replace(/^\/+/, '')}`, API_BASE_URL).toString();
}
