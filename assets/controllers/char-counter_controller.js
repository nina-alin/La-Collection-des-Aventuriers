import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'counter', 'submit'];
    static values = { max: { type: Number, default: 1000 } };

    connect() {
        this._update();
    }

    update() {
        this._update();
    }

    _update() {
        if (!this.hasInputTarget) return;
        const count = this.inputTarget.value.length;
        const max = this.maxValue;
        const over = count > max;

        if (this.hasCounterTarget) {
            this.counterTarget.textContent = `${count} / ${max}`;
        }

        this.element.classList.toggle('is-over-limit', over);

        if (this.hasSubmitTarget) {
            this.submitTarget.disabled = over;
        }
    }
}
