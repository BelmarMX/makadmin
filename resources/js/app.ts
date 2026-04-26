import { createInertiaApp } from '@inertiajs/vue3';
import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import Ripple from 'primevue/ripple';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const PrimeVueOverride = definePreset(Aura, {
    semantic: {
        colorScheme: {
            light: {
                primary: {
                    50: '{violet.50}',
                    100: '{violet.100}',
                    200: '{violet.200}',
                    300: '{violet.300}',
                    400: '{violet.400}',
                    500: '{violet.500}',
                    600: '{violet.600}',
                    700: '{violet.700}',
                    800: '{violet.800}',
                    900: '{violet.900}',
                    950: '{violet.950}',
                },
            },
            dark: {
                primary: {
                    50: '{cyan.50}',
                    100: '{cyan.100}',
                    200: '{cyan.200}',
                    300: '{cyan.300}',
                    400: '{cyan.400}',
                    500: '{cyan.500}',
                    600: '{cyan.600}',
                    700: '{cyan.700}',
                    800: '{cyan.800}',
                    900: '{cyan.900}',
                    950: '{cyan.950}',
                },
            },
        },
    },
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#f29c49',
    },
    withApp: (app) => {
        app.use(PrimeVue, {
            ripple: true,
            theme: {
                preset: PrimeVueOverride,
                options: {
                    darkModeSelector: '.dark',
                },
            },
        });
        app.use(ToastService);
        app.directive('ripple', Ripple);
        app.directive('tooltip', Tooltip);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
