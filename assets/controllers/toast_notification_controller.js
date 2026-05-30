import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        message:  { type: String, default: '' },
        type:     { type: String, default: 'success' },
        position: { type: String, default: 'top-right' },
    };

    connect() {
        this._render();
        this._autoDismiss = setTimeout(() => this.dismiss(), 4000);
        this.element.addEventListener('click', () => this.dismiss());
    }

    disconnect() {
        clearTimeout(this._autoDismiss);
    }

    dismiss() {
        this.element.classList.add('toast-hiding');
        setTimeout(() => this.element.remove(), 300);
    }

    _render() {
        const positionClass = window.innerWidth < 1080 ? 'toast-top-center' : `toast-${this.positionValue}`;
        this.element.classList.add('toast-notification', `toast-${this.typeValue}`, positionClass);
        this.element.setAttribute('role', 'alert');
        this.element.setAttribute('aria-live', this.typeValue === 'error' ? 'assertive' : 'polite');
        this.element.textContent = this.messageValue;
    }
}
