import type { ApiResponse } from './types.ts';
import { t } from './i18n.ts';

/**
 * apiFetch — central HTTP helper for all API calls.
 *
 * Mirrors the legacy apiFetch() helper from layouts/app.blade.php.
 * Reads the CSRF token from <meta name="csrf-token">, injects it as
 * X-CSRF-TOKEN, handles 429 rate-limiting and non-JSON bodies gracefully.
 *
 * @param url    - Absolute or relative URL to fetch
 * @param method - HTTP method (defaults to 'GET')
 * @param body   - Optional URL-encoded request body string
 */
export async function apiFetch<T extends Record<string, unknown> = Record<string, unknown>>(
    url: string,
    method: string = 'GET',
    body?: string,
): Promise<ApiResponse<T>> {
    const csrfMeta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    const csrfToken = csrfMeta?.content ?? '';

    const headers: Record<string, string> = {
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (body !== undefined && body !== '') {
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    let response: Response;
    try {
        response = await fetch(url, {
            method: method.toUpperCase(),
            headers,
            body: body !== undefined && body !== '' ? body : undefined,
        });
    } catch {
        return { response: 'error', text: t('network-error', 'Network error') } as ApiResponse<T>;
    }

    if (response.status === 429) {
        return {
            response: 'error',
            text: t('too-many-requests', 'Too many requests. Please wait a moment and try again.'),
        } as ApiResponse<T>;
    }

    try {
        return (await response.json()) as ApiResponse<T>;
    } catch {
        return { response: 'error', text: 'Unknown error' } as ApiResponse<T>;
    }
}

/**
 * apiFetchJson — like apiFetch but sends a JSON body (used for passkey endpoints).
 */
export async function apiFetchJson<T extends Record<string, unknown> = Record<string, unknown>>(
    url: string,
    method: string = 'POST',
    body?: unknown,
): Promise<ApiResponse<T>> {
    const csrfMeta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    const csrfToken = csrfMeta?.content ?? '';

    let response: Response;
    try {
        response = await fetch(url, {
            method: method.toUpperCase(),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body !== undefined ? JSON.stringify(body) : undefined,
        });
    } catch {
        return { response: 'error', text: t('network-error', 'Network error') } as ApiResponse<T>;
    }

    if (response.status === 429) {
        return {
            response: 'error',
            text: t('too-many-requests', 'Too many requests. Please wait a moment and try again.'),
        } as ApiResponse<T>;
    }

    try {
        return (await response.json()) as ApiResponse<T>;
    } catch {
        return { response: 'error', text: 'Unknown error' } as ApiResponse<T>;
    }
}
