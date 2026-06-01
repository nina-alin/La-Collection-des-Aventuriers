import { Controller } from '@hotwired/stimulus';

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export default class extends Controller {
    static targets = ['fab', 'modal', 'overlay'];

    openModal() {
        const modal = this.modalTarget;
        modal.style.display = '';
        modal.removeAttribute('hidden');
        modal.classList.add('is-open');
        this.overlayTarget.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        this._trapFocus(modal);
        document.addEventListener('keydown', this._onKeydown);
    }

    closeModal() {
        const modal = this.modalTarget;
        modal.classList.remove('is-open');
        this.overlayTarget.classList.remove('is-open');
        document.body.style.overflow = '';
        if (this.hasFabTarget) this.fabTarget.focus();
        document.removeEventListener('keydown', this._onKeydown);
        // Hide after transition
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    }

    _trapFocus(modal) {
        const focusable = [...modal.querySelectorAll(FOCUSABLE)];
        if (focusable.length) focusable[0].focus();
    }

    _onKeydown = (event) => {
        if (event.key === 'Escape') {
            this.closeModal();
            return;
        }
        if (event.key === 'Tab') {
            const modal     = this.modalTarget;
            const focusable = [...modal.querySelectorAll(FOCUSABLE)];
            if (!focusable.length) return;
            const first = focusable[0];
            const last  = focusable[focusable.length - 1];
            if (event.shiftKey) {
                if (document.activeElement === first) { event.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        }
    };
}
