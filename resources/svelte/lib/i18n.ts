/**
 * Client-side translation helper.
 *
 * The LANGUAGE global is injected by the server via:
 *   <script src="/lang/{locale}.js"></script>
 * which declares: const LANGUAGE = { ... };
 *
 * Values may be plain strings or arrays of strings (some keys hold lists).
 * The `t()` helper always returns a single string, joining arrays with a
 * newline when encountered.
 */

declare global {
    // eslint-disable-next-line no-var
    var LANGUAGE: Record<string, string | string[]> | undefined;
}

export function t(key: string, fallback?: string): string {
    if (typeof window !== 'undefined' && window.LANGUAGE !== undefined) {
        const value = window.LANGUAGE[key];
        if (value !== undefined) {
            return Array.isArray(value) ? value.join('\n') : value;
        }
    }
    return fallback ?? key;
}
