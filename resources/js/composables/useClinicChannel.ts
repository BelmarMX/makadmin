import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { onUnmounted } from 'vue';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'>;
    }
}

function getEcho(): Echo<'reverb'> {
    if (!window.Echo) {
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    }

    return window.Echo;
}

export function useClinicChannel(
    clinicId: number,
    topic: string,
    events: Record<string, (event: unknown) => void>,
): void {
    const echo = getEcho();
    const channel = echo.private(`clinic.${clinicId}.${topic}`);

    for (const [event, handler] of Object.entries(events)) {
        channel.listen(event, handler);
    }

    onUnmounted(() => {
        echo.leave(`clinic.${clinicId}.${topic}`);
    });
}
