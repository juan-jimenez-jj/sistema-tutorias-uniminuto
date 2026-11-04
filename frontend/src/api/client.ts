/**
 * Cliente HTTP de la API REST (/api/v1).
 * - La sesión viaja en una cookie HttpOnly que el navegador envía sola.
 * - En métodos que modifican datos se copia la cookie legible tut_csrf al header
 *   X-CSRF-Token (patrón double-submit), como exige el backend.
 * - Los errores llegan como application/problem+json (RFC 7807) y se convierten en ApiError.
 */
export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: string,
    message: string,
    public readonly fields: Record<string, string> = {},
  ) {
    super(message);
  }
}

const BASE = '/api/v1';

function csrfToken(): string {
  const match = document.cookie.match(/(?:^|;\s*)tut_csrf=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : '';
}

let onUnauthorized: () => void = () => {};
export function setUnauthorizedHandler(handler: () => void) {
  onUnauthorized = handler;
}

export async function api<T>(path: string, options: { method?: string; body?: unknown; signal?: AbortSignal } = {}): Promise<T> {
  const method = options.method ?? 'GET';
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (options.body !== undefined) headers['Content-Type'] = 'application/json';
  if (method !== 'GET') headers['X-CSRF-Token'] = csrfToken();

  let response: Response;
  try {
    response = await fetch(BASE + path, {
      method,
      headers,
      credentials: 'same-origin',
      body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
      signal: options.signal,
    });
  } catch {
    throw new ApiError(0, 'network', 'No hay conexión con el servidor. Revisa tu red e intenta de nuevo.');
  }

  if (response.status === 204) return undefined as T;
  const isJson = (response.headers.get('Content-Type') ?? '').includes('json');
  const data = isJson ? await response.json() : null;

  if (!response.ok) {
    if (response.status === 401 && !path.startsWith('/auth/login')) onUnauthorized();
    throw new ApiError(
      response.status,
      data?.code ?? 'error',
      data?.detail ?? `Error ${response.status}`,
      data?.errors ?? {},
    );
  }
  return data as T;
}

export function qs(params: Record<string, string | number | undefined | null>): string {
  const search = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') search.set(k, String(v));
  });
  const s = search.toString();
  return s ? `?${s}` : '';
}
