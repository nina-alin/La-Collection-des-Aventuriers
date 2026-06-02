import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this._onReadAll = this._handleReadAll.bind(this);
        this._onRedirect = this._handleRedirect.bind(this);

        window.addEventListener('notification:panel:read-all', this._onReadAll);
        window.addEventListener('notification:panel:redirect', this._onRedirect);
    }

    disconnect() {
        window.removeEventListener('notification:panel:read-all', this._onReadAll);
        window.removeEventListener('notification:panel:redirect', this._onRedirect);
    }

    _handleReadAll() {
        document.querySelectorAll('.notif-badge').forEach(badge => {
            badge.textContent = '0';
            badge.hidden = true;
        });
    }

    _handleRedirect(event) {
        const targetUrl = event.detail?.targetUrl;
        if (targetUrl) {
            window.location.href = targetUrl;
        }
    }
}
