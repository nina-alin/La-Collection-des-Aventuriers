import { Controller } from '@hotwired/stimulus';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export default class extends Controller {
    triggerElement = null;

    open(event) {
        this.triggerElement = event.currentTarget;
        this.element.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        this.trapFocus();
    }

    close() {
        this.element.classList.remove('is-open');
        document.body.style.overflow = '';
        this.triggerElement?.focus();
        this.triggerElement = null;
    }

    trapFocus() {
        const focusable = [...this.element.querySelectorAll(FOCUSABLE)];
        if (focusable.length) focusable[0].focus();
    }

    handleKeydown(event) {
        if (event.key === 'Escape') {
            this.close();
            return;
        }
        if (event.key === 'Tab' && this.element.classList.contains('is-open')) {
            const focusable = [...this.element.querySelectorAll(FOCUSABLE)];
            if (!focusable.length) return;
            const first = focusable[0];
            const last  = focusable[focusable.length - 1];
            if (event.shiftKey) {
                if (document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }
    }

    handleBackdropClick(event) {
        if (event.target === this.element) {
            this.close();
        }
    }
}
