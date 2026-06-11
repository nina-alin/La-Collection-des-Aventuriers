import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        token: String,
        followed: Boolean,
        authenticated: Boolean,
    };

    static targets = ['button', 'icon', 'label'];

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!this.authenticatedValue) {
            window.dispatchEvent(new CustomEvent('follow:open-login-modal'));
            return;
        }

        const wasFollowed = this.followedValue;
        this._setOptimistic(!wasFollowed);

        const body = new URLSearchParams();
        body.set('_token', this.tokenValue);

        fetch(this.urlValue, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        })
            .then(res => {
                if (!res.ok) throw new Error('server_error');
                return res.json();
            })
            .then(data => {
                this.followedValue = data.followed;
                this.tokenValue    = data.token;
                this._applyState(data.followed);
                this._enable();
            })
            .catch(() => {
                this._setOptimistic(wasFollowed);
                this._enable();
                this.dispatch('error', {
                    detail: { message: "Une erreur est survenue. Votre action n'a pas été enregistrée." },
                    bubbles: true,
                });
                setTimeout(() => {}, 4000);
            });
    }

    _setOptimistic(followed) {
        this._disable();
        this._applyState(followed);
    }

    _applyState(followed) {
        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-pressed', followed ? 'true' : 'false');
        }
        if (this.hasLabelTarget) {
            this.labelTarget.textContent = followed ? 'Suivi' : 'Suivre';
        }
        if (this.hasIconTarget) {
            this.iconTarget.classList.toggle('is-followed', followed);
        }
    }

    _disable() {
        if (this.hasButtonTarget) this.buttonTarget.disabled = true;
    }

    _enable() {
        if (this.hasButtonTarget) this.buttonTarget.disabled = false;
    }
}
