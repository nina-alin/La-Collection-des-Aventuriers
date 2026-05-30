import { Controller } from '@hotwired/stimulus';

const MAX_SIZE = 4 * 1024 * 1024;
const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
const STATES = ['idle', 'drag-over', 'invalid', 'loading', 'success'];

export default class extends Controller {
    static targets = ['input', 'state'];

    connect() {
        this.element.addEventListener('dragover', this.onDragOver.bind(this));
        this.element.addEventListener('dragleave', this.onDragLeave.bind(this));
        this.element.addEventListener('drop', this.onDrop.bind(this));
    }

    onDragOver(e) {
        e.preventDefault();
        this.setStateClass('drag-over');
    }

    onDragLeave() {
        this.setStateClass('idle');
    }

    onDrop(e) {
        e.preventDefault();
        const file = e.dataTransfer?.files?.[0];
        if (file) this.handleFile(file);
    }

    handleFileChange(e) {
        const file = e.target.files?.[0];
        if (file) this.handleFile(file);
    }

    handleFile(file) {
        if (!ALLOWED_MIMES.includes(file.type)) {
            this.setStateClass('invalid');
            this.showError('Format non supporté. Utilisez JPEG, PNG ou WEBP.');
            this.resetInput();
            return;
        }
        if (file.size > MAX_SIZE) {
            this.setStateClass('invalid');
            this.showError('Fichier trop volumineux. Maximum 4 Mo.');
            this.resetInput();
            return;
        }
        this.setStateClass('loading');
        this.dispatch('upload', { detail: { file } });
    }

    setStateClass(state) {
        STATES.forEach(s => this.element.classList.toggle(`upload-${s}`, s === state));
    }

    showError(message) {
        let errorEl = this.element.querySelector('[data-upload-error]');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.setAttribute('data-upload-error', '');
            errorEl.setAttribute('role', 'alert');
            errorEl.setAttribute('aria-live', 'assertive');
            errorEl.className = 'upload-error mt-2 text-danger';
            this.element.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }

    resetInput() {
        if (this.hasInputTarget) {
            this.inputTarget.value = '';
        }
    }
}
