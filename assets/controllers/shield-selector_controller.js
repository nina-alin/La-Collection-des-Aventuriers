import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['pip', 'input'];

    connect() {
        const container = this.element.querySelector('[role="radiogroup"]');
        if (!container) return;

        const selectedVal = this.inputTarget?.value ? parseInt(this.inputTarget.value, 10) : 0;
        this._updatePips(selectedVal);
    }

    select(event) {
        const pip = event.currentTarget;
        const val = parseInt(pip.dataset.val, 10);
        this._setValue(val);
    }

    keydown(event) {
        const pip = event.currentTarget;
        const val = parseInt(pip.dataset.val, 10);

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            if (val > 1) {
                this._setValue(val - 1);
                this._focusPip(val - 1);
            }
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            if (val < 10) {
                this._setValue(val + 1);
                this._focusPip(val + 1);
            }
        } else if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this._setValue(val);
        }
    }

    _setValue(val) {
        if (this.hasInputTarget) {
            this.inputTarget.value = val;
        }
        this._updatePips(val);
    }

    _updatePips(selectedVal) {
        this.pipTargets.forEach(pip => {
            const pipVal = parseInt(pip.dataset.val, 10);
            pip.classList.toggle('is-on', pipVal <= selectedVal);
            pip.setAttribute('aria-checked', pipVal === selectedVal ? 'true' : 'false');
        });
    }

    _focusPip(val) {
        const pip = this.pipTargets.find(p => parseInt(p.dataset.val, 10) === val);
        if (pip) pip.focus();
    }
}
