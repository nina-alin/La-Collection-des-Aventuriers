import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['bell', 'panel'];

    connect() {
        this._onClickOutside = this._handleClickOutside.bind(this);
        this._onLiveError = this._handleLiveError.bind(this);
        document.addEventListener('click', this._onClickOutside);
        this.element.addEventListener('live:connect-error', this._onLiveError);
    }

    disconnect() {
        document.removeEventListener('click', this._onClickOutside);
        this.element.removeEventListener('live:connect-error', this._onLiveError);
    }

    toggle() {
        const panel = this.panelTarget;
        const bell = this.bellTarget;
        const isOpen = panel.classList.contains('is-open');

        panel.classList.toggle('is-open', !isOpen);
        panel.hidden = isOpen;
        bell.setAttribute('aria-expanded', String(!isOpen));
    }

    _handleClickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.panelTarget.classList.remove('is-open');
            this.panelTarget.hidden = true;
            this.bellTarget.setAttribute('aria-expanded', 'false');
        }
    }

    _handleLiveError() {
        document.dispatchEvent(new CustomEvent('toast:show', {
            bubbles: true,
            detail: { message: 'Impossible de charger les notifications.', type: 'error' },
        }));
    }
}
