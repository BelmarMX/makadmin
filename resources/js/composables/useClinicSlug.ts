export function clinicSlug(): string {
    if (typeof window === 'undefined') {
        return '';
    }

    return window.location.hostname.split('.')[0];
}
