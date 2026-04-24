export * from './auth';
export * from './navigation';
export * from './ui';

export interface BrandingSharedData {
    apexDomain: string;
    publicSubdomain: string;
    superadminSubdomain: string;
    portalSubdomain: string;
    scheme: string;
}

export type AppContext = 'admin' | 'clinic' | 'app';

export interface SharedPageProps {
    name: string;
    branding: BrandingSharedData;
    auth: import('./auth').Auth;
    context: AppContext;
    sidebarOpen: boolean;
    [key: string]: unknown;
}
