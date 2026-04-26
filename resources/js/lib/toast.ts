import ToastEventBus from 'primevue/toasteventbus';

type Severity = 'success' | 'error' | 'info' | 'warn';

function add(severity: Severity, detail: string, life = 3000): void {
    ToastEventBus.emit('add', { severity, summary: '', detail, life });
}

export const toast = {
    success: (msg: string, life?: number) => add('success', msg, life),
    error: (msg: string, life?: number) => add('error', msg, life),
    info: (msg: string, life?: number) => add('info', msg, life),
    warning: (msg: string, life?: number) => add('warn', msg, life),
};
