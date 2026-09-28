import './bootstrap';
import { confirmDialog, initConfirmHelpers } from './confirm';

initConfirmHelpers();

document.addEventListener('alpine:init', () => {
    if (window.Alpine && typeof window.Alpine.data === 'function') {
        window.Alpine.data('confirmDialog', confirmDialog);
    }
});

import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });