import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'preview', 'error', 'form'];

    connect() {
        if (this.hasInputTarget) {
            this.inputTarget.addEventListener('change', (e) => this.onFileSelected(e));
        }
    }

    onFileSelected(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            this.showError('Le fichier dépasse 2 Mo.');
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            this.showError('Format non supporté. Utilisez JPEG, PNG ou WebP.');
            return;
        }

        this.clearError();

        const reader = new FileReader();
        reader.onload = (e) => {
            if (this.hasPreviewTarget) {
                this.setAvatarSrc(this.previewTarget, e.target.result);
            }
        };
        reader.readAsDataURL(file);
    }

    async submit(event) {
        event.preventDefault();
        const form = this.hasFormTarget ? this.formTarget : this.element.closest('form');
        if (!form) return;

        const formData = new FormData(form);
        this.clearError();

        try {
            const res = await fetch(form.action, { method: 'POST', body: formData });
            const data = await res.json();

            if (!res.ok) {
                this.showError(data.error ?? 'Erreur lors de l\'upload.');
                return;
            }

            if (this.hasPreviewTarget && data.avatarUrl) {
                this.setAvatarSrc(this.previewTarget, data.avatarUrl);
            }

            document.querySelectorAll('[data-hero-avatar]').forEach(el => {
                this.setAvatarSrc(el, data.avatarUrl);
            });

            this.dispatch('success');
        } catch {
            this.showError('Erreur de connexion.');
        }
    }

    setAvatarSrc(el, src) {
        if (el.tagName === 'IMG') {
            el.src = src;
            return;
        }
        let img = el.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%';
            el.appendChild(img);
        }
        img.src = src;
    }

    showError(msg) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = msg;
            this.errorTarget.hidden = false;
        }
    }

    clearError() {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = '';
            this.errorTarget.hidden = true;
        }
    }
}
