import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal'];

    connect() {
        this._dirty = false;
        this._boundBeforeUnload = this.onBeforeUnload.bind(this);
        this._boundLinkClick = this.onLinkClick.bind(this);

        window.addEventListener('beforeunload', this._boundBeforeUnload);
        document.addEventListener('click', this._boundLinkClick);

        this.element.addEventListener('live:connect-error', this.onLiveError.bind(this));
        this.element.addEventListener('live:render-error', this.onLiveError.bind(this));
        this.element.addEventListener('live:connect', this.onLiveConnect.bind(this));
    }

    disconnect() {
        window.removeEventListener('beforeunload', this._boundBeforeUnload);
        document.removeEventListener('click', this._boundLinkClick);
    }

    markDirty() {
        this._dirty = true;
    }

    markClean() {
        this._dirty = false;
    }

    onBeforeUnload(e) {
        if (!this._dirty) return;
        e.preventDefault();
        e.returnValue = '';
    }

    onLinkClick(e) {
        if (!this._dirty) return;
        const anchor = e.target.closest('a[href]');
        if (!anchor || anchor.closest('[data-live-component]')) return;
        if (anchor.href.startsWith(window.location.origin + window.location.pathname)) return;
        e.preventDefault();
        this.showModal(anchor.href);
    }

    showModal(destination) {
        const modal = document.createElement('div');
        modal.className = 'abandon-modal-backdrop';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', 'Confirmation de navigation');
        modal.innerHTML = `
            <div class="abandon-modal card p-4" style="max-width:400px;margin:auto;">
                <p class="mb-3">Vous avez des modifications non sauvegardées. Quitter la page ?</p>
                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary" data-action="stay">Rester</button>
                    <button type="button" class="btn btn-danger" data-action="leave">Quitter</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const firstBtn = modal.querySelector('button');
        firstBtn?.focus();

        modal.querySelector('[data-action="stay"]').addEventListener('click', () => modal.remove());
        modal.querySelector('[data-action="leave"]').addEventListener('click', () => {
            this._dirty = false;
            window.location.href = destination;
        });
    }

    onLiveError() {
        this.disableInteraction();
        this.dispatch('toast', {
            bubbles: true,
            detail: {
                message: 'Connexion perdue — vos données sont préservées, réessayez.',
                type: 'error',
            },
        });
    }

    onLiveConnect() {
        this.enableInteraction();
    }

    disableInteraction() {
        this.element.querySelectorAll('button[type="button"], input[type="submit"]')
            .forEach(el => el.setAttribute('disabled', 'disabled'));
    }

    enableInteraction() {
        this.element.querySelectorAll('button[disabled], input[disabled]')
            .forEach(el => el.removeAttribute('disabled'));
    }
}
