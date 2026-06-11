import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.observer = new MutationObserver(() => {
            if (this.element.children.length > 3) {
                this.element.lastElementChild.remove();
            }
        });
        this.observer.observe(this.element, { childList: true });

        if (this._toastHandler) {
            document.removeEventListener('toast', this._toastHandler);
        }

        this._toastHandler = (event) => {
            const { message, type } = event.detail;
            const typeClass = type === 'error' ? 'danger' : type;
            const role = (typeClass === 'danger' || typeClass === 'warning') ? 'alert' : 'status';
            const icons = { success: '✓', danger: '✕', warning: '!', info: 'i' };
            const icon = icons[typeClass] ?? 'i';
            const toast = document.createElement('div');
            toast.setAttribute('data-controller', 'toast');
            toast.setAttribute('data-toast-auto-dismiss-ms-value', '5000');
            toast.className = `toast ${typeClass}`;
            toast.setAttribute('role', role);
            toast.innerHTML = `<span class="toast-icon">${icon}</span><div class="toast-body"><span class="toast-title">${message}</span></div><button class="toast-close" data-action="toast#close" type="button" aria-label="Fermer"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M6 18L18 6"/></svg></button><span class="toast-timer"></span>`;
            this.element.prepend(toast);
        };
        document.addEventListener('toast', this._toastHandler);
    }

    disconnect() {
        this.observer?.disconnect();
        document.removeEventListener('toast', this._toastHandler);
        this._toastHandler = null;
    }
}
