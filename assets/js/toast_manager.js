
class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.maxToasts = 5;
        this.defaultDuration = 5000;
        this.init();
    }
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            this.container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 400px;
                pointer-events: none;
            `;
            document.body.appendChild(this.container);
        }
    }
    show(message, type = 'info', duration = this.defaultDuration) {
        if (this.toasts.length >= this.maxToasts) {
            this.remove(this.toasts[0]);
        }
        const toast = this.createToast(message, type, duration);
        this.toasts.push(toast);
        this.container.appendChild(toast);
        if (duration > 0) {
            setTimeout(() => this.remove(toast), duration);
        }
        return toast;
    }
    createToast(message, type, duration) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const colors = {
            success: { bg: '#28a745', icon: '✓' },
            error: { bg: '#dc3545', icon: '✕' },
            warning: { bg: '#ffc107', icon: '⚠', textColor: '#000' },
            info: { bg: '#17a2b8', icon: 'ℹ' }
        };
        const config = colors[type] || colors.info;
        const textColor = config.textColor || '#fff';
        toast.style.cssText = `
            background: ${config.bg};
            color: ${textColor};
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            line-height: 1.4;
            max-width: 100%;
            word-wrap: break-word;
            pointer-events: auto;
            cursor: pointer;
            transform: translateX(100%);
            transition: transform 0.3s ease, opacity 0.3s ease;
            border-left: 4px solid rgba(255,255,255,0.3);
        `;
        toast.innerHTML = `
            <span class="toast-icon" style="font-size: 16px; font-weight: bold;">${config.icon}</span>
            <span class="toast-message" style="flex: 1;">${this.escapeHtml(message)}</span>
            <span class="toast-close" style="font-size: 18px; opacity: 0.7; margin-left: 8px;">×</span>
        `;
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });
        toast.addEventListener('click', () => this.remove(toast));
        return toast;
    }
    remove(toast) {
        if (!toast || !toast.parentElement) return;
        const index = this.toasts.indexOf(toast);
        if (index > -1) {
            this.toasts.splice(index, 1);
        }
        toast.style.transform = 'translateX(100%)';
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    }
    removeAll() {
        [...this.toasts].forEach(toast => this.remove(toast));
    }
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text.toString();
        return div.innerHTML;
    }
    success(message, duration) {
        return this.show(message, 'success', duration);
    }
    error(message, duration) {
        return this.show(message, 'error', duration);
    }
    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }
    info(message, duration) {
        return this.show(message, 'info', duration);
    }
}
window.toastManager = new ToastManager();
function showNotification(message, type = 'info', duration = 5000) {
    return window.toastManager.show(message, type, duration);
}
window.showNotification = showNotification;
window.Toast = {
    show: showNotification,
    success: (msg, duration) => window.toastManager.success(msg, duration),
    error: (msg, duration) => window.toastManager.error(msg, duration),
    warning: (msg, duration) => window.toastManager.warning(msg, duration),
    info: (msg, duration) => window.toastManager.info(msg, duration),
    removeAll: () => window.toastManager.removeAll()
};