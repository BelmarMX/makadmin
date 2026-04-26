declare module 'primevue/toasteventbus' {
    import { EventBus } from '@primeuix/utils/eventbus';
    const ToastEventBus: ReturnType<typeof EventBus>;
    export default ToastEventBus;
}
