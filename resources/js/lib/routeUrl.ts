/**
 * Resolves a Wayfinder route URL to a same-origin relative path.
 *
 * Wayfinder bakes the full domain (e.g. `//{clinic}.makadmin.test/users`)
 * into generated route files at build time. When APP_APEX_DOMAIN changes,
 * those URLs become invalid.
 *
 * This utility strips the domain portion, using the current origin instead.
 *
 * @example
 *   routeUrl({ url: '//clinic-a.makadmin.test/users' })
 *   // → '/users'
 *
 *   routeUrl({ url: '//radar.makadmin.test/clinics/3' })
 *   // → '/clinics/3'
 */
export function routeUrl(definition: { url: string }): string {
    const url = definition.url;

    if (!url.startsWith('//')) {
        return url;
    }

    const pathIndex = url.indexOf('/', 2);

    return pathIndex !== -1 ? url.slice(pathIndex) : '/';
}
