export interface ApiErrorPayload {
    message?: string;
    errors?: Record<string, string[]>;
}

export class ApiError extends Error {
    status: number;
    payload: ApiErrorPayload | null;

    constructor(message: string, status: number, payload: ApiErrorPayload | null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.payload = payload;
    }
}

interface RequestOptions {
    method?: string;
    body?: unknown;
    query?: Record<string, string | number | null | undefined>;
}

export function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match?.[1] ? decodeURIComponent(match[1]) : '';
}

export async function ensureCsrfCookie(): Promise<void> {
    await fetch('/sanctum/csrf-cookie', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
}

export async function http<T>(path: string, { method = 'GET', body, query }: RequestOptions = {}): Promise<T> {
    const url = new URL(path, window.location.origin);

    if (query) {
        Object.entries(query).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, String(value));
            }
        });
    }

    const token = xsrfToken();
    let response: Response;

    try {
        response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
                ...(token ? { 'X-XSRF-TOKEN': token } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch {
        throw new ApiError('No hay conexión con el servidor.', 0, null);
    }

    const contentType = response.headers.get('content-type') ?? '';
    const payload = contentType.includes('application/json')
        ? await response.json() as ApiErrorPayload & T
        : null;

    if (!response.ok) {
        throw new ApiError(payload?.message || 'No se pudo completar la solicitud.', response.status, payload);
    }

    return payload as T;
}

export function api<T>(path: string, options: RequestOptions = {}): Promise<T> {
    return http<T>(`/api${path}`, options);
}

export function firstError(value: string | string[] | undefined): string {
    if (Array.isArray(value)) {
        return value[0] ?? '';
    }

    return value ?? '';
}
