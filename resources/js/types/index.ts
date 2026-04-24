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

export interface SharedPageProps {
    name: string;
    branding: BrandingSharedData;
    auth: import('./auth').Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}
