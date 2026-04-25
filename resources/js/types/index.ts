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

export interface Clinic {
    id: number;
    slug: string;
    commercial_name: string;
    legal_name: string;
    logo_path?: string | null;
    logo_url?: string | null;
    [key: string]: unknown;
}

export interface SharedPageProps {
    name: string;
    branding: BrandingSharedData;
    auth: import('./auth').Auth;
    context: AppContext;
    sidebarOpen: boolean;
    [key: string]: unknown;
}
