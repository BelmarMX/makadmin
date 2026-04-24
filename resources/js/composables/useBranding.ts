import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedPageProps } from '@/types';

export function useBranding() {
    const page = usePage<SharedPageProps>();

    return {
        branding: computed(() => page.props.branding),
        apexDomain: computed(() => page.props.branding.apexDomain),
        publicSubdomain: computed(() => page.props.branding.publicSubdomain),
        superadminSubdomain: computed(() => page.props.branding.superadminSubdomain),
        portalSubdomain: computed(() => page.props.branding.portalSubdomain),
        scheme: computed(() => page.props.branding.scheme),
    };
}
