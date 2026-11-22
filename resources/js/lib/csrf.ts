export function getXsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

/**
 * Resolves fresh CSRF headers from Laravel's rotating XSRF-TOKEN cookie
 * so fetch() calls remain valid after Inertia SPA login/logout navigation.
 */
export function getCsrfHeaders(): Record<string, string> {
    const xsrfToken = getXsrfToken();
    if (xsrfToken) {
        return { 'X-XSRF-TOKEN': xsrfToken };
    }

    if (typeof document !== 'undefined') {
        const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content;
        if (metaToken) {
            return { 'X-CSRF-TOKEN': metaToken };
        }
    }

    return {};
}
