export async function api(path, { method = 'GET', body, query } = {}) {
    const url = new URL(`/api${path}`, window.location.origin);

    if (query) {
        Object.entries(query).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });
    }

    let response;

    try {
        response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                ...(body ? { 'Content-Type': 'application/json' } : {}),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch {
        const error = new Error('No hay conexión con el servidor.');
        error.payload = null;
        throw error;
    }

    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
        const error = new Error(payload?.message || 'No se pudo completar la solicitud.');
        error.status = response.status;
        error.payload = payload;
        throw error;
    }

    return payload;
}

export function firstError(value) {
    if (Array.isArray(value)) {
        return value[0] || '';
    }

    return value || '';
}
