export function confirmDialog() {
    return {
        open: false,
        title: 'Konfirmasi',
        message: '',
        confirmText: 'Ya, Lanjutkan',
        danger: false,
        onConfirm: null,

        show(options = {}) {
            this.title = options.title || 'Konfirmasi';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Ya, Lanjutkan';
            this.danger = options.danger || false;
            this.onConfirm = options.onConfirm || null;
            this.open = true;
        },

        close() {
            this.open = false;
            this.onConfirm = null;
        },

        confirm() {
            const cb = this.onConfirm;
            this.open = false;
            this.onConfirm = null;
            if (typeof cb === 'function') cb();
        },
    };
}

function getModalData() {
    const el = document.getElementById('stockku-confirm-modal');
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        return el._x_dataStack[0];
    }
    return null;
}

function ask(message, onConfirm, options = {}) {
    const data = getModalData();
    if (data) {
        data.show({ message, onConfirm, ...options });
    } else if (window.confirm) {
        if (window.confirm(message)) onConfirm();
    }
}

export function initConfirmHelpers() {
    window.StockKuConfirm = { ask };

    window.confirmForm = function (form, message, options = {}) {
        ask(message, () => form.submit(), options);
        return false;
    };

    window.confirmEvent = function (event, message, options = {}) {
        const target = event.currentTarget;
        event.preventDefault();
        ask(message, () => {
            if (target.form) {
                target.form.submit();
            }
        }, options);
        return false;
    };
}