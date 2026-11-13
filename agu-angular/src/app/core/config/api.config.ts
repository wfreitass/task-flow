/** Root URL shared by all frontend repositories that call the Symfony API. */
export const API_BASE_URL = 'http://localhost:8000/';

/** Builds an API URL without coupling a feature to the host or the `/api` prefix. */
export function apiUrl(path: string): string {
  return new URL(`api/${path.replace(/^\/+/, '')}`, API_BASE_URL).toString();
}
